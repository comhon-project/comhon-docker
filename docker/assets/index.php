<?php

/*
 * This file is part of the Comhon Docker package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Comhon\Api\RequestHandler;
use Comhon\Object\Config\Config;
use Comhon\Api\Response;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Model\NotDefinedModelException;
use Comhon\Utils\Project\ModelSqlSerializer;
use Comhon\Utils\Project\ModelBinder;
use Comhon\Utils\Project\ModelToSQL;
use Comhon\Database\DatabaseHandler;
use Comhon\Model\ModelCustom;
use Comhon\Model\Property\Property;
use Comhon\Model\ModelArray;
use Comhon\Exception\HTTP\ResponseException;
use Comhon\Exception\HTTP\MalformedRequestException;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Model\Model;
use Comhon\Utils\Utils;
use Comhon\Api\ResponseBuilder;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context){});

$config_af = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
Config::setLoadPath($config_af);

$requestableModels_af = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'requestable_models.json';
$requestableModels = json_decode(file_get_contents($requestableModels_af), true);
$resolver = function ($pathModelName) use ($requestableModels) {
    $key = strtolower($pathModelName);
    return array_key_exists($key, $requestableModels) ? $requestableModels[$key] : null;
};
// Comhon framework handle common requests
$response = RequestHandler::handle('/api/comhon', $resolver);

// specifics requests 
if ($response->getStatusCode() == 404 && $response->getFullBodyContents() == 'not handled route') {
	$matches = null;
	$method = $_SERVER['REQUEST_METHOD'];
	$path = RequestHandler::getFilteredServerRequestPath(true);
	
	if (preg_match('/^\\/api\\/namespace\\/(\\w+)$/', $path, $matches) && $method == 'POST') {
		$response = createNamespace($matches[1], $config_af);
	} elseif (preg_match('/^\\/api\\/serialize\\/(\\w+(\\\\\\w+)*)$/', $path, $matches) && $method == 'POST') {
		$response = createModelSerialization($matches[1]);
	} elseif (preg_match('/^\\/api\\/aggregation\\/(\\w+(\\\\\\w+)*)$/', $path, $matches) && $method == 'POST') {
		$response = transformPropertyToAggregation($matches[1]);
	} elseif (preg_match('/^\\/api\\/pattern\\/(\\w+)$/', $path, $matches) && ($method == 'POST' || $method == 'PUT')) {
	    $response = createOrUpdatePattern($matches[1], $method);
	} elseif (preg_match('/^\\/api\\/models$/', $path, $matches)) {
	    $response = getModelNames($requestableModels);
	}
}

// request is handled by comhon framework but need some specifics handling
if ($response->getStatusCode() == 201) {
    $matches = null;
    $path = RequestHandler::getFilteredServerRequestPath(true);
    if (preg_match('/^\\/api\\/comhon\\/manifest$/', $path, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') {
		addApiModelName($response, $requestableModels, $requestableModels_af);
	}
}

$response->send();

/**
 * add namespace prefix in config file
 * 
 * @param string $namespace
 * @param string $config_af
 * @return \Comhon\Api\Response
 */
function createNamespace($namespace, $config_af) {
	$response = new Response();
	if (Config::getInstance()->getManifestAutoloadList()->hasValue($namespace)) {
	    $response = ResponseBuilder::buildSimpleResponse(409, [], "namespace $namespace already exists");
	} else {
		$success = true;
		foreach (['manifest', 'serialization', 'options'] as $key) {
			$namespacePath_rd = '..' . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . $namespace;
			$directory = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $namespacePath_rd;
			if (mkdir($directory)) {
				Config::getInstance()->getValue('autoload')->getValue($key)->setValue($namespace, $namespacePath_rd);
			} else {
			    $response = ResponseBuilder::buildSimpleResponse(500, [], 'something goes wrong during directory creation');
				$success = false;
				break;
			}
		}
		if ($success) {
			$interfacer = new StdObjectInterfacer();
			if(!$interfacer->write($interfacer->export(Config::getInstance()), $config_af, true)) {
			    $response = ResponseBuilder::buildSimpleResponse(500, [], 'something goes wrong during saving config');
			}
		}
	}
	return $response;
}

/**
 * create or update pattern regex
 * 
 * @param string $pattern
 * @param string $method
 * @return \Comhon\Api\Response
 */
function createOrUpdatePattern($pattern, $method) {
	$new = $method == 'POST';
	$regex = file_get_contents('php://input');
	$regexs = json_decode(file_get_contents(Config::getInstance()->getRegexListPath()), true);
	
	if (preg_match($regex, '') === false) {
	    $response = ResponseBuilder::buildSimpleResponse(400, [], 'invalid regex');
	} elseif ($new && array_key_exists($pattern, $regexs)) {
	    $response = ResponseBuilder::buildSimpleResponse(409, [], "pattern $pattern already exists");
	} elseif (!$new && !array_key_exists($pattern, $regexs)) {
	    $response = ResponseBuilder::buildSimpleResponse(404, [], "pattern $pattern doesn't exists");
	}else {
		$regexs[$pattern] = $regex;
		file_put_contents(Config::getInstance()->getRegexListPath(), json_encode($regexs, JSON_PRETTY_PRINT));
		$response = new Response($new ? 201 : 200);
	}
	return $response;
}

/**
 * create or update SQL serialization for provided model :
 * - create or update serialization manifest file 
 * - create or update SQL table in database
 * - create or update inheritance values
 * 
 * @param string $modelName
 * @throws \Exception
 * @return \Comhon\Api\Response
 */
function createModelSerialization($modelName) {
	$response = new Response();
	try {
		ModelManager::getInstance()->getInstanceModel($modelName); // verify if model exists
		$modelDatabase = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
		$dbId = getenv('DEFAULT_DATABASE_ID');
		if ($dbId === false) {
			throw new \Exception('environnement variable \'DEFAULT_DATABASE_ID\' is not defined');
		}
		$sqlDatabase = $modelDatabase->loadObject($dbId);
		if (is_null($sqlDatabase)) {
			throw new \Exception("default database $dbId not found");
		}
		$script = new ModelSqlSerializer(false);
		$script->registerSerializations($sqlDatabase, 'snake', $modelName, false);
		ModelManager::resetSingleton();
		$script = new ModelBinder(false);
		$script->bindModels($modelName, false);
		ModelManager::resetSingleton();
		// execute ModelSqlSerializer again because ModelBinder may create serialization files on inherited models
		// for inheritance values (without serialization node)
		// if we are in this case :
		// before executing ModelBinder, ModelSqlSerializer didn't know if inherited models have same serialization
		// but by creating serialization file, ModelSqlSerializer knows they use same serialization
		// and inheritance key may be added
		// (reload database model and object due to models reinitialization)
		$modelDatabase = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
		$sqlDatabase = $modelDatabase->loadObject($dbId);
		$script = new ModelSqlSerializer(false);
		$script->registerSerializations($sqlDatabase, 'snake', $modelName, false);
		ModelManager::resetSingleton();
		
		$script = new ModelToSQL(false);
		$queries = $script->generateQueries(true, $modelName, false);
		
		foreach ($queries as $databaseId => $query) {
			$dbHandler = DatabaseHandler::getInstanceWithDataBaseId($databaseId);
			$dbHandler->getPDO()->exec($query);
		}
	} catch (NotDefinedModelException $e) {
	    $response = ResponseBuilder::buildSimpleResponse(404, [], "resource model '$modelName' doesn't exist");
	} catch (\Exception $e) {
		$response = ResponseBuilder::buildSimpleResponse(500, [], $e->getMessage());
	}
	return $response;
}

/**
 * transform a property to aggregation property
 * 
 * @param string $modelName
 * @throws ResponseException
 * @throws MalformedRequestException
 * @return \Comhon\Api\Response
 */
function transformPropertyToAggregation($modelName) {
    $response = new Response();
	try {
		try {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
		} catch (NotDefinedModelException $e) {
			throw new ResponseException(404, "resource model '{$modelName}' doesn't exist");
		}
		$interfacer = RequestHandler::getInterfacerFromContentTypeHeader(RequestHandler::getServerRequest());
		$modelPropertyAggregation = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest\Property\Aggregation');
		$modelAggregations = $modelPropertyAggregation->getProperty('aggregations')->getModel();
		
		$stringModel = ModelManager::getInstance()->getInstanceModel('string');
		$name = new Property($stringModel, 'name');
		$aggregations = new Property($modelAggregations, 'aggregations');
		
		$bodyModel = new ModelArray(
			new ModelCustom('AggregationProperty', [$name, $aggregations]),
			false,
			'property'
		);
		$aggregationProperties = RequestHandler::importBody(
		    RequestHandler::getServerRequest(),
			$bodyModel,
			$interfacer
		);
		
		$parentProperties = [];
		foreach ($model->getParents() as $parentModel) {
			$parentProperties = array_merge($parentProperties, $parentModel->getProperties());
		}
		$modelProperties = array_diff_key($model->getProperties(), $parentProperties);
		foreach ($aggregationProperties as $property) {
			$property->getValue('name');
			if (!array_key_exists($property->getValue('name'), $modelProperties)) {
				throw new MalformedRequestException(
					"property '{$property->getValue('name')}' is not defined on model '{$model->getName()}'"
				);
			}
			if (!$model->getProperty($property->getValue('name')) instanceof ForeignProperty) {
				throw new MalformedRequestException(
					"property '{$property->getValue('name')}' on model '{$model->getName()}' is not a foreign property"
				);
			}
			if (!$model->getProperty($property->getValue('name'))->getModel()->getModel() instanceof ModelArray) {
				throw new MalformedRequestException(
					"property '{$property->getValue('name')}' on model '{$model->getName()}' is not an array property"
				);
			}
			if (!$model->getProperty($property->getValue('name'))->getModel()->getModel()->getModel() instanceof Model) {
				throw new MalformedRequestException(
					"property '{$property->getValue('name')}' on model '{$model->getName()}' must not be multi dimensional array property"
				);
			}
			$propertyModel = $model->getProperty($property->getValue('name'))->getUniqueModel();
			foreach ($property->getValue('aggregations') as $propertyName) {
				if (!$propertyModel->hasProperty($propertyName)) {
					throw new MalformedRequestException(
						"property '{$propertyName}' doesn't exist on model '{$propertyModel->getName()}'"
					);
				}
				if (!$propertyModel->getProperty($propertyName) instanceof ForeignProperty) {
					throw new MalformedRequestException(
						"property '{$propertyName}' on model '{$propertyModel->getName()}' is not a foreign property"
					);
				}
				if (!$propertyModel->getProperty($propertyName)->getModel()->getModel() instanceof Model) {
					throw new MalformedRequestException(
						"property '{$propertyName}' on model '{$propertyModel->getName()}' must not be array property"
					);
				}
				$aggregationPropertyModel = $propertyModel->getProperty($propertyName)->getModel()->getModel();
				if ($aggregationPropertyModel !== $model && !$aggregationPropertyModel->isInheritedFrom($model)) {
					throw new MalformedRequestException(
						"property '{$propertyName}' on model '{$propertyModel->getName()}' is not a '{$model->getName()}'"
					);
				}
			}
		}
		$modelManifest = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest');
		$manifest = $modelManifest->loadObject($model->getName());
		$manifestProperties = $manifest->getValue('properties');
		$indexProperties = [];
		foreach ($manifestProperties as $i => $property) {
			$indexProperties[$property->getValue('name')] = $i;
		}
		foreach ($aggregationProperties as $property) {
			$index = $indexProperties[$property->getValue('name')];
			$manifestPorperty = $manifestProperties->getValue($index);
			
			$modelAggregation = ModelManager::getInstance()->getInstanceModel('Comhon\Manifest\Property\Aggregation');
			$newManifestProperty = $modelAggregation->getObjectInstance(false);
			$newManifestProperty->setValue('name', $manifestPorperty->getValue('name'));
			
			if ($manifestPorperty->hasValue('not_empty')) {
				$newManifestProperty->setValue('not_empty', $manifestPorperty->getValue('not_empty'));
			}
			if ($manifestPorperty->hasValue('size')) {
				$newManifestProperty->setValue('size', $manifestPorperty->getValue('size'));
			}
			$newManifestProperty->setValue('values', $manifestPorperty->getValue('values'));
			$newManifestProperty->getValue('values')->unsetValue('is_foreign');
			$newManifestProperty->setValue('aggregations', $property->getValue('aggregations'));
			
			$manifestProperties->setValue($index, $newManifestProperty);
		}
		$manifest->save();
		
	} catch (ResponseException $e) {
		$response = $e->getResponse();
	} catch (\Exception $e) {
		$response = ResponseBuilder::buildSimpleResponse(500, [], $e->getMessage());
	}
	return $response;
}

function getModelNames(array $requestableModels) {
    $method = RequestHandler::getServerRequest()->getMethod();
    if ($method == 'OPTIONS') {
        return new Response(200, ['Allow' => implode(', ', ['GET', 'HEAD', 'OPTIONS'])]);
    }
    if ($method != 'GET' && $method != 'HEAD') {
        return new Response(405, ['Allow' => implode(', ', ['GET', 'HEAD', 'OPTIONS'])], "method $method not allowed");
    }
    $interfacer = RequestHandler::getInterfacerFromAcceptHeader(RequestHandler::getServerRequest());
    
    if ($interfacer instanceof XMLInterfacer) {
        $node = $interfacer->createArrayNode('root');
        foreach ($requestableModels as $apiName => $modelName) {
            $interfacer->addAssociativeValue($node, $modelName, $apiName, 'node');
        }
    } elseif ($interfacer instanceof AssocArrayInterfacer) {
        $node = $requestableModels;
    } else {
        throw new \Exception('not handled Content-Type : '.get_class($interfacer));
    }
    $body = $interfacer->toString($node);
    $headers = ['Content-Type' => $interfacer->getMediaType()];
    if ($method == 'HEAD') {
        $headers['Content-Length'] = strlen($body);
        $body = '';
    }
    return new Response(200, $headers, $body);
}

/**
 * add api model name in requestable models list for provided model (in response object)
 * 
 * @param Response $response
 * @param string[] $requestableModels
 * @param string $requestableModels_af
 * @throws ResponseException
 */
function addApiModelName(Response $response, $requestableModels, $requestableModels_af) {
    $content = $response->getFullBodyContents();
    
    $interfacer = Interfacer::getInstance($response->getHeaderLine('Content-Type'));
	$modelName = $interfacer->getValue($interfacer->fromString($content), 'name');
	$apiModelName = Utils::toKebabCase(str_replace('\\', '-', $modelName));
	if (array_key_exists($apiModelName, $requestableModels)) {
		$i = 2;
		while (array_key_exists($apiModelName.$i, $requestableModels)) {
			$i++;
		}
		$apiModelName .= $i;
	}
	$requestableModels[$apiModelName] = $modelName;
	file_put_contents($requestableModels_af, json_encode($requestableModels, JSON_PRETTY_PRINT));
}


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
use Comhon\Database\SelectQuery;
use Comhon\Logic\Clause;
use Comhon\Database\SimpleDbLiteral;
use Comhon\Model\StringCastableModelInterface;
use Comhon\Api\ApiModelNameHandlerInterface;
use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpException;
use Psr\Http\Server\RequestHandlerInterface;
use Comhon\Interfacer\XMLInterfacer;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context){});

class ApiModelNameHandler implements ApiModelNameHandlerInterface
{
    /**
     *
     * @var array
     */
    private $models = [];
    
    /**
     *
     * @param array $models
     */
    public function __construct()
    {
        $requestableModels_af = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'requestable_models.json';
        $this->models = json_decode(file_get_contents($requestableModels_af), true);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Comhon\Api\ApiModelNameHandlerInterface::useApiModelName()
     */
    public function useApiModelName(): bool
    {
        return true;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Comhon\Api\ApiModelNameHandlerInterface::resolveApiModelName()
     */
    public function resolveApiModelName(string $apiModelName, Request $request): ?string
    {
        $apiModelName = strtolower($apiModelName);
        if (!is_null($this->models)) {
            foreach ($this->models as $model) {
                if (
                    isset($model[ApiModelNameHandlerInterface::API_MODEL_NAME_KEY])
                    && $model[ApiModelNameHandlerInterface::API_MODEL_NAME_KEY] === $apiModelName
                ) {
                    return $model[ApiModelNameHandlerInterface::COMHON_MODEL_NAME_KEY];
                }
            }
        }
        return null;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \Comhon\Api\ApiModelNameHandlerInterface::getApiModels()
     */
    public function getApiModels(Request $request): ?array
    {
        return $this->models;
    }
    
}

$config_af = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
Config::setLoadPath($config_af);

$authMiddleware = function (Request $request, RequestHandlerInterface $handler) {
    if ($request->getMethod() !== 'OPTIONS' && $request->getUri()->getPath() !== '/api/login') {
        $account = getAuth($request);
        $request = $request->withAttribute('account', $account);
    }
    return $handler->handle($request);
};
$AccessControlMiddleware = function (Request $request, RequestHandlerInterface $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', '*')
        ->withHeader('Access-Control-Allow-Methods', '*')
        ->withHeader('Access-Control-Expose-Headers', '*');
};

$addApiModelNameMiddleware = function (Request $request, RequestHandlerInterface $handler) {
    $response = $handler->handle($request);
    if (
        $response->getStatusCode() == 201
        && $request->getMethod() == 'POST'
        && RequestHandler::filterPath($request->getUri()->getPath()) === '/api/comhon/manifest' 
    ) {
        addApiModelName($response);
    }
    return $response;
};

$factory = new class () implements ResponseFactoryInterface {
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
};

$app = AppFactory::create($factory);
$app->add($authMiddleware);
$app->add($AccessControlMiddleware);

// Comhon framework handle common requests
$app->any('/api/comhon[/{resource:.*}]', new RequestHandler('/api/comhon', new ApiModelNameHandler()))
    ->add($addApiModelNameMiddleware);

// other specific requests
$app->options('/api/login', function (Request $request, Response $response, $args) {
    return $response;
});
$app->post('/api/login', function (Request $request, Response $response, $args) {
    return login($request);
});
$app->post('/api/namespace/{namespace:\\w+}', function (Request $request, Response $response, $args) {
    return createNamespace($args['namespace'], Config::getLoadPath());
});
$app->post('/api/serialize/{model:\\w+(?:\\\\\\w+)*}', function (Request $request, Response $response, $args) {
    return createModelSerialization($args['model']);
});
$app->post('/api/aggregation/{model:\\w+(?:\\\\\\w+)*}', function (Request $request, Response $response, $args) {
    return transformPropertyToAggregation($request, $args['model']);
});
$app->map(['PUT', 'POST'], '/api/pattern/{pattern:\\w+}', function (Request $request, Response $response, $args) {
    return createOrUpdatePattern($args['pattern'], $request->getMethod());
});

try {
    $app->run();
} catch (HttpException $e) {
    $header = [
        'Content-Type' => 'text/plain',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Headers' => '*'
    ];
    $response = new Response($e->getCode(), $header, $e->getDescription());
    $response->send();
} catch (ResponseException $e) {
    $response =  $e->getResponse()
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', '*');
    $response->send();
}

function login(Request $request) {
    $post = $request->getParsedBody();
    
    if (!Config::getInstance()->issetValue('authentication')) {
        return new Response(404);
    }
    $authConfig = Config::getInstance()->getValue('authentication');
    try {
        $accountModel = ModelManager::getInstance()->getInstanceModel(
            Config::getInstance()->getValue('account_model')
        );
    } catch (\Exception $e) {
        var_dump($e->getMessage());
        return new Response(500, [], 'something goes wrong during account model loading');
    }
    if (!$accountModel->hasSqlTableSerialization()) {
        return new Response(500, [], 'account model doesn\'t have serialization');
    }
    if (!isset($post['username']) || !isset($post['password'])) {
        return new Response(400, [], 'missing username or/and password');
    }
    if (count($accountModel->getIdProperties()) !== 1) {
        return new Response(500, [], 'account model must have one and only one id property');
    }
    $idProperties = $accountModel->getIdProperties();
    $idProperty = current($idProperties);
    $tableName = $accountModel->getSerializationSettings()->getValue('name');
    $query = new SelectQuery($tableName);
    $table = $query->getMainTable();
    $table->addSelectedColumn($idProperty->getSerializationName());
    $clause = new Clause(Clause::CONJUNCTION);
    $clause->addLiteral(new SimpleDbLiteral(
        $table,
        $accountModel->getProperty($authConfig->getValue('account_identifier_property_name'), true)->getSerializationName(),
        '=',
        $post['username']
    ));
    $clause->addLiteral(new SimpleDbLiteral(
        $table,
        $accountModel->getProperty($authConfig->getValue('account_password_property_name'), true)->getSerializationName(),
        '=',
        md5($post['password'])
    ));
    $query->where($clause);
    try {
        $dbHandler = DatabaseHandler::getInstanceWithDataBaseId(
            $accountModel->getSerializationSettings()->getValue('database')->getId()
        );
        $res = $dbHandler->select($query);
    } catch (\Exception $e) {
        return new Response(500, [], 'something goes wrong during database loading or query execution');
    }
    
    if (count($res) == 0) {
        return new Response(404, [], 'wrong user or wrong password');
    }
    if (count($res) > 1) {
        return new Response(500, [], 'several same user');
    }
    $idPropertyModel = $idProperty->getModel();
    $id = $idPropertyModel instanceof StringCastableModelInterface
        ? $idPropertyModel->castValue(current($res[0]))
        : current($res[0]);
    
    $time = time();
    $payload = array(
        "iat" => $time,
        "nbf" => $time,
        "exp" => $time + 3600,
        "username" => $post['username'],
        "uid" => $id
    );
    $jwt = JWT::encode($payload, file_get_contents('/var/private'), 'HS256');
    
    return new Response(200, [], $jwt);
}

function getAuth(Request $request) {
    if (!Config::getInstance()->issetValue('authentication')) {
        return null;
    }
    if (!$request->hasHeader('Authorization')) {
        if (Config::getInstance()->getValue('authentication')->getValue('is_required')) {
            throw new ResponseException(401, 'Authentication required');
        }
        return null;
    }
    if (!Config::getInstance()->issetValue('account_model')) {
        throw new ResponseException(500, 'invalid Authentication configuration, account_model not defined');
    }
    $auth = $request->getHeaderLine('Authorization');
    if (strpos($auth, 'Bearer ') !== 0) {
        throw new ResponseException(401, 'invalid JWT, header value must be prefixed by "Bearer "');
    }
    try {
        $payload = JWT::decode(substr($auth, 7), file_get_contents('/var/private'), ['HS256']);
    } catch (Exception $e) {
        throw new ResponseException(401, $e->getMessage());
    }
    try {
        $accountModel = ModelManager::getInstance()->getInstanceModel(
            Config::getInstance()->getValue('account_model')
        );
    } catch (\Exception $e) {
        throw new ResponseException(500, 'invalid Authentication configuration, failure when loading account_model');
    }
    $account = $accountModel->loadObject($payload->uid);
    if (is_null($account)) {
        throw new ResponseException(500, 'account not found');
    }
    return $account;
}

/**
 * add namespace prefix in config file
 * 
 * @param string $namespace
 * @param string $config_af
 * @return \Comhon\Api\Response
 */
function createNamespace($namespace, $config_af) {
    $response = new Response();
    $config = Config::getInstance();
    if ($config->getManifestAutoloadList()->hasValue($namespace)) {
        $response = ResponseBuilder::buildSimpleResponse(409, [], "namespace $namespace already exists");
    } else {
        $success = true;
        foreach (['manifest', 'serialization', 'options'] as $key) {
            $namespacePath_rd = '..' . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . $namespace;
            $directory = $config->getDirectory() . DIRECTORY_SEPARATOR . $namespacePath_rd;
            if (mkdir($directory)) {
                $config->getValue('autoload')->getValue($key)->setValue($namespace, $namespacePath_rd);
            } else {
                $response = ResponseBuilder::buildSimpleResponse(500, [], 'something goes wrong during directory creation');
                $success = false;
                break;
            }
        }
        if ($success) {
            $interfacer = new StdObjectInterfacer();
            $interfacer->setPrivateContext(true);
            if(!$interfacer->write($config->getModel()->getParent()->export($config, $interfacer), $config_af, true)) {
                $response = ResponseBuilder::buildSimpleResponse(500, [], 'something goes wrong during saving config');
            }
            if (!is_null(ModelManager::getInstance()->getCacheHandler())) {
                ModelManager::getInstance()->getCacheHandler()->reset();
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
        if (!Config::getInstance()->isA('Docker\Config')) {
            throw new \Exception(
                'config must be a "Docker\Config", '
                .'but instanciated config is a "'.Config::getInstance()->getModel()->getName().'"'
            );
        }
        ModelManager::getInstance()->getInstanceModel($modelName); // verify if model exists
        $modelDatabase = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
        if (!Config::getInstance()->issetValue('default_database')) {
            throw new \Exception('default_database is not defined in config file');
        }
        $sqlDatabase = Config::getInstance()->getValue('default_database');
        $sqlDatabase->load();
        if (!$sqlDatabase->isLoaded()) {
            throw new \Exception("default database {$sqlDatabase->getId()} not found");
        }
        $script = new ModelSqlSerializer(false);
        $script->registerSerializations($sqlDatabase, 'snake', $modelName, false);
        ModelManager::resetSingleton();
        if (!is_null(ModelManager::getInstance()->getCacheHandler())) {
            ModelManager::getInstance()->getCacheHandler()->reset();
        }
        $script = new ModelBinder(false);
        $script->bindModels($modelName, false);
        ModelManager::resetSingleton();
        if (!is_null(ModelManager::getInstance()->getCacheHandler())) {
            ModelManager::getInstance()->getCacheHandler()->reset();
        }
        // execute ModelSqlSerializer again because ModelBinder may create serialization files on inherited models
        // for inheritance values (without serialization node)
        // if we are in this case :
        // before executing ModelBinder, ModelSqlSerializer didn't know if inherited models have same serialization
        // but by creating serialization file, ModelSqlSerializer knows they use same serialization
        // and inheritance key may be added
        // (reload database model and object due to models reinitialization)
        $modelDatabase = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
        $sqlDatabase = $modelDatabase->loadObject($sqlDatabase->getId());
        $script = new ModelSqlSerializer(false);
        $script->registerSerializations($sqlDatabase, 'snake', $modelName, false);
        ModelManager::resetSingleton();
        if (!is_null(ModelManager::getInstance()->getCacheHandler())) {
            ModelManager::getInstance()->getCacheHandler()->reset();
        }
        
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
function transformPropertyToAggregation(Request $request, $modelName) {
    $response = new Response();
    try {
        try {
            $model = ModelManager::getInstance()->getInstanceModel($modelName);
        } catch (NotDefinedModelException $e) {
            throw new ResponseException(404, "resource model '{$modelName}' doesn't exist");
        }
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
        $aggregationProperties = RequestHandler::importBody($request, $bodyModel);
        
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

/**
 * add api model name in requestable models list for provided model (in response object)
 * 
 * @param Response $response
 * @throws ResponseException
 */
function addApiModelName(Response $response) {
    $requestableModels_af = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'requestable_models.json';
    $requestableModels = json_decode(file_get_contents($requestableModels_af), true);
    $map = [];
    $content = $response->getFullBodyContents();
    foreach ($requestableModels as $requestableModel) {
        if (isset($requestableModel[ApiModelNameHandlerInterface::API_MODEL_NAME_KEY])) {
            $map[$requestableModel[ApiModelNameHandlerInterface::API_MODEL_NAME_KEY]] = null;
        }
    }
    
    $interfacer = Interfacer::getInstance($response->getHeaderLine('Content-Type'));
    $manifest = $interfacer->fromString($content);
    $modelName = $interfacer->getValue($manifest, 'name');
    $apiModelName = Utils::toKebabCase(str_replace('\\', '-', $modelName));
    if (array_key_exists($apiModelName, $map)) {
        $i = 2;
        while (array_key_exists($apiModelName.$i, $map)) {
            $i++;
        }
        $apiModelName .= $i;
    }
    $newRequestableModel = [
        ApiModelNameHandlerInterface::COMHON_MODEL_NAME_KEY => $modelName,
        ApiModelNameHandlerInterface::API_MODEL_NAME_KEY => $apiModelName
    ];
    if ($interfacer->hasValue($manifest, 'extends', true)) {
        $parentModels = $interfacer->getTraversableNode($interfacer->getValue($manifest, 'extends', true));
        if ($interfacer instanceof XMLInterfacer) {
            foreach ($parentModels as $key => $domNode) {
                $parentModels[$key] = $interfacer->extractNodeText($domNode);
            }
        }
        if (!empty($parentModels)) {
            $newRequestableModel[ApiModelNameHandlerInterface::EXTENDS_KEY] = [];
            foreach ($parentModels as $parentModelName) {
                $parentModelName = $parentModelName[0] == '\\' 
                    ? substr($parentModelName, 1) 
                    : $modelName. '\\' . $parentModelName;
                $newRequestableModel[ApiModelNameHandlerInterface::EXTENDS_KEY][] = $parentModelName;
            }
        }
    }
    $requestableModels[] = $newRequestableModel;
    file_put_contents($requestableModels_af, json_encode($requestableModels, JSON_PRETTY_PRINT));
}

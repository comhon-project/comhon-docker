--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.24
-- Dumped by pg_dump version 13.1 (Ubuntu 13.1-1.pgdg18.04+1)

-- Started on 2021-01-13 18:42:39 CET

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 1 (class 3079 OID 12403)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 2296 (class 0 OID 0)
-- Dependencies: 1
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET default_tablespace = '';

SET default_with_oids = false;

DROP TABLE IF EXISTS public.person;
DROP TABLE IF EXISTS public.place;
DROP TABLE IF EXISTS public.house;
DROP TABLE IF EXISTS public.account;
DROP TABLE IF EXISTS public.test;

--
-- TOC entry 181 (class 1259 OID 1284646)
-- Name: account; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.account (
    id bigint NOT NULL,
    username character varying(30) NOT NULL,
    password character(32) NOT NULL
);


ALTER TABLE public.account OWNER TO root;

--
-- TOC entry 182 (class 1259 OID 1284649)
-- Name: account_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.account_id_seq OWNER TO root;

--
-- TOC entry 2197 (class 0 OID 0)
-- Dependencies: 182
-- Name: account_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.account_id_seq OWNED BY public.account.id;


--
-- TOC entry 183 (class 1259 OID 1284651)
-- Name: house; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.house (
    id bigint NOT NULL,
    surface real,
    garden boolean,
    owner_id bigint
);


ALTER TABLE public.house OWNER TO root;

--
-- TOC entry 184 (class 1259 OID 1284654)
-- Name: house_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.house_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.house_id_seq OWNER TO root;

--
-- TOC entry 2198 (class 0 OID 0)
-- Dependencies: 184
-- Name: house_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.house_id_seq OWNED BY public.house.id;


--
-- TOC entry 185 (class 1259 OID 1284656)
-- Name: person; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.person (
    id bigint NOT NULL,
    first_name text,
    last_name text,
    gender text NOT NULL,
    birth_place_id integer,
    father_id integer,
    mother_id integer,
    birth_date timestamp with time zone
);


ALTER TABLE public.person OWNER TO root;

--
-- TOC entry 186 (class 1259 OID 1284662)
-- Name: person_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.person_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.person_id_seq OWNER TO root;

--
-- TOC entry 2199 (class 0 OID 0)
-- Dependencies: 186
-- Name: person_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.person_id_seq OWNED BY public.person.id;


--
-- TOC entry 187 (class 1259 OID 1284664)
-- Name: place; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.place (
    id bigint NOT NULL,
    number integer,
    type text,
    name text,
    town text
);


ALTER TABLE public.place OWNER TO root;

--
-- TOC entry 188 (class 1259 OID 1284670)
-- Name: place_id_seq; Type: SEQUENCE; Schema: public; Owner: root
--

CREATE SEQUENCE public.place_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.place_id_seq OWNER TO root;

--
-- TOC entry 2200 (class 0 OID 0)
-- Dependencies: 188
-- Name: place_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.place_id_seq OWNED BY public.place.id;


--
-- TOC entry 189 (class 1259 OID 1284686)
-- Name: test; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.test (
    name character varying(255) NOT NULL,
    string_value text,
    float_value double precision,
    integer_value integer,
    index_value integer,
    boolean_value boolean,
    percentage_value double precision,
    date_value timestamp without time zone,
    object_value text,
    object_values text,
    object_container text,
    foreign_object_values text,
    object_ref_parent text
);


ALTER TABLE public.test OWNER TO root;

--
-- TOC entry 2052 (class 2604 OID 1284672)
-- Name: account id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.account ALTER COLUMN id SET DEFAULT nextval('public.account_id_seq'::regclass);


--
-- TOC entry 2053 (class 2604 OID 1284673)
-- Name: house id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.house ALTER COLUMN id SET DEFAULT nextval('public.house_id_seq'::regclass);


--
-- TOC entry 2054 (class 2604 OID 1284674)
-- Name: person id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.person ALTER COLUMN id SET DEFAULT nextval('public.person_id_seq'::regclass);


--
-- TOC entry 2055 (class 2604 OID 1284675)
-- Name: place id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.place ALTER COLUMN id SET DEFAULT nextval('public.place_id_seq'::regclass);


--
-- TOC entry 2182 (class 0 OID 1284646)
-- Dependencies: 181
-- Data for Name: account; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.account VALUES (1, 'test', '01bf424ed73a006501d51fb42f646cb6');
INSERT INTO public.account VALUES (2, 'test2', '788a29c48e3ef8790f15e0340e7330e0');


--
-- TOC entry 2184 (class 0 OID 1284651)
-- Dependencies: 183
-- Data for Name: house; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.house VALUES (1, 110, false, 1);
INSERT INTO public.house VALUES (2, 130, true, 2);
INSERT INTO public.house VALUES (3, 120, true, 2);


--
-- TOC entry 2186 (class 0 OID 1284656)
-- Dependencies: 185
-- Data for Name: person; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.person VALUES (1, 'john', 'doe', 'Sample\Person\Man', 1, NULL, NULL, '1972-11-13 21:04:05+01');
INSERT INTO public.person VALUES (2, 'jane', 'doe', 'Sample\Person\Woman', 2, NULL, NULL, '1970-01-13 21:04:05+01');
INSERT INTO public.person VALUES (3, 'marie', 'doe', 'Sample\Person\Woman', 3, 1, 2, '1995-11-10 21:04:05+01');
INSERT INTO public.person VALUES (4, 'philippe', 'doe', 'Sample\Person\Man', 3, 1, 2, '1998-05-01 22:04:05+02');
INSERT INTO public.person VALUES (5, 'emilie', 'doe', 'Sample\Person\Woman', 2, 1, NULL, '1994-06-23 22:02:59+02');
INSERT INTO public.person VALUES (6, 'walter', 'doe', 'Sample\Person\Man', 2, NULL, 5, '2016-09-21 22:02:59+02');
INSERT INTO public.person VALUES (7, 'jesse', 'doe', 'Sample\Person\Man', 2, NULL, 5, '2018-10-04 22:02:59+02');


--
-- TOC entry 2188 (class 0 OID 1284664)
-- Dependencies: 187
-- Data for Name: place; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.place VALUES (1, 16, 'street', 'main street', 'New York');
INSERT INTO public.place VALUES (2, 3, 'street', 'second street', 'New York');
INSERT INTO public.place VALUES (3, 10, 'avenue', 'Jean Moulin', 'Paris');


--
-- TOC entry 2190 (class 0 OID 1284686)
-- Dependencies: 189
-- Data for Name: test; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.test VALUES ('first', 'string', 1.5, 2, 5, true, 0.5, '2021-01-13 17:28:50.940751', '{"id":"1","propertyOne":"plop1","propertyTwo":"plop11"}', '[{"id":"2","propertyOne":"plop2","propertyTwo":"plop22"},{"id":"3","propertyOne":"plop3","propertyTwo":"plop33"}]', '{"foreignObjectValue":3,"objectValueTwo":{"id":"1","propertyTwoOne":"2plop1","propertyTwoOneTwo":"2plop11"}}', '[1,3]', '{"parent":"first","name":"first"}');
INSERT INTO public.test VALUES ('second', 'string', 1.5, 2, 5, true, 0.5, '2021-01-13 17:28:50.940751', '{"id":"1","propertyOne":"plopplop1","propertyTwo":"plopplop11"}', '[{"id":"2","propertyOne":"plopplop2","propertyTwo":"plopplop22"},{"id":"3","propertyOne":"plopplop3","propertyTwo":"plopplop33"}]', '{"foreignObjectValue":3,"objectValueTwo":{"id":"1","propertyTwoOne":"2plopplop1","propertyTwoOneTwo":"2plopplop11"}}', '[1,3]', '{"parent":"second","name":"second"}');

--
-- TOC entry 2201 (class 0 OID 0)
-- Dependencies: 182
-- Name: account_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.account_id_seq', 2, true);


--
-- TOC entry 2202 (class 0 OID 0)
-- Dependencies: 184
-- Name: house_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.house_id_seq', 3, true);


--
-- TOC entry 2203 (class 0 OID 0)
-- Dependencies: 186
-- Name: person_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.person_id_seq', 11, true);


--
-- TOC entry 2204 (class 0 OID 0)
-- Dependencies: 188
-- Name: place_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.place_id_seq', 4, true);


--
-- TOC entry 2057 (class 2606 OID 1284679)
-- Name: account account_name_unique_key; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.account
    ADD CONSTRAINT account_name_unique_key UNIQUE (username);


--
-- TOC entry 2059 (class 2606 OID 1284677)
-- Name: account account_pkey; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.account
    ADD CONSTRAINT account_pkey PRIMARY KEY (id);


--
-- TOC entry 2061 (class 2606 OID 1284681)
-- Name: house house_pkey; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.house
    ADD CONSTRAINT house_pkey PRIMARY KEY (id);


--
-- TOC entry 2063 (class 2606 OID 1284683)
-- Name: person pk_person; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.person
    ADD CONSTRAINT pk_person PRIMARY KEY (id);


--
-- TOC entry 2065 (class 2606 OID 1284685)
-- Name: place pk_place; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.place
    ADD CONSTRAINT pk_place PRIMARY KEY (id);


--
-- TOC entry 2067 (class 2606 OID 1284693)
-- Name: test test_pkey; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.test
    ADD CONSTRAINT test_pkey PRIMARY KEY (name);


--
-- TOC entry 2196 (class 0 OID 0)
-- Dependencies: 7
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2021-01-13 18:42:39 CET

--
-- PostgreSQL database dump complete
--

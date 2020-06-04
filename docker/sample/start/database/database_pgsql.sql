--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.20
-- Dumped by pg_dump version 12.1 (Ubuntu 12.1-1.pgdg18.04+1)

-- Started on 2019-12-30 03:17:30 CET

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

--
-- TOC entry 193 (class 1259 OID 304437)
-- Name: person; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.person (
    id bigint NOT NULL,
    first_name text,
    last_name text,
    sex text NOT NULL,
    birth_place integer,
    father_id integer,
    mother_id integer,
    birth_date timestamp with time zone,
    best_friend integer
);


ALTER TABLE public.person OWNER TO root;

--
-- TOC entry 194 (class 1259 OID 304443)
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
-- TOC entry 2310 (class 0 OID 0)
-- Dependencies: 194
-- Name: person_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.person_id_seq OWNED BY public.person.id;


--
-- TOC entry 195 (class 1259 OID 304445)
-- Name: place; Type: TABLE; Schema: public; Owner: root
--

CREATE TABLE public.place (
    id bigint NOT NULL,
    number integer,
    type text,
    name text,
    geographic_latitude double precision,
    geographic_longitude double precision,
    town text
);


ALTER TABLE public.place OWNER TO root;

--
-- TOC entry 196 (class 1259 OID 304451)
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
-- TOC entry 2311 (class 0 OID 0)
-- Dependencies: 196
-- Name: place_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: root
--

ALTER SEQUENCE public.place_id_seq OWNED BY public.place.id;

--
-- TOC entry 2116 (class 2604 OID 304494)
-- Name: person id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.person ALTER COLUMN id SET DEFAULT nextval('public.person_id_seq'::regclass);


--
-- TOC entry 2117 (class 2604 OID 304495)
-- Name: place id; Type: DEFAULT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.place ALTER COLUMN id SET DEFAULT nextval('public.place_id_seq'::regclass);


--
-- TOC entry 2286 (class 0 OID 304437)
-- Dependencies: 193
-- Data for Name: person; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.person VALUES (1, 'Bernard', 'Dupond', 'Sample\Person\Man', 2, NULL, NULL, '2016-11-13 20:04:05+01', NULL);
INSERT INTO public.person VALUES (5, 'Jean', 'Henri', 'Sample\Person\Man', NULL, 1, 2, '2016-11-13 20:04:05+01', 7);
INSERT INTO public.person VALUES (6, 'john', 'Lennon', 'Sample\Person\Man', NULL, 1, 2, '2016-11-13 20:04:05+01', NULL);
INSERT INTO public.person VALUES (2, 'Marie', 'Smith', 'Sample\Person\Woman', NULL, NULL, NULL, '2016-11-13 20:04:05+01', 5);
INSERT INTO public.person VALUES (7, 'Lois', 'Lane', 'Sample\Person\Woman', NULL, NULL, NULL, '2016-11-13 20:02:59+01', NULL);
INSERT INTO public.person VALUES (8, 'Louise', 'Doe', 'Sample\Person\Woman', NULL, 6, 7, NULL, 9);
INSERT INTO public.person VALUES (9, 'Nathanaël', 'Dupond', 'Sample\Person\Man', NULL, 6, 7, NULL, NULL);
INSERT INTO public.person VALUES (11, 'Naelya', 'Dupond', 'Sample\Person\Woman', 2, 1, NULL, NULL, NULL);


--
-- TOC entry 2288 (class 0 OID 304445)
-- Dependencies: 195
-- Data for Name: place; Type: TABLE DATA; Schema: public; Owner: root
--

INSERT INTO public.place VALUES (1, 1, 'square', 'George Frêche', NULL, NULL, 'Montpellier');
INSERT INTO public.place VALUES (2, 16, 'street', 'Trocmé', NULL, NULL, 'Montpellier');

--
-- TOC entry 2321 (class 0 OID 0)
-- Dependencies: 194
-- Name: person_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.person_id_seq', 11, true);


--
-- TOC entry 2322 (class 0 OID 0)
-- Dependencies: 196
-- Name: place_id_seq; Type: SEQUENCE SET; Schema: public; Owner: root
--

SELECT pg_catalog.setval('public.place_id_seq', 2, true);

--
-- TOC entry 2145 (class 2606 OID 304518)
-- Name: person pk_person; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.person
    ADD CONSTRAINT pk_person PRIMARY KEY (id);


--
-- TOC entry 2147 (class 2606 OID 304520)
-- Name: place pk_place; Type: CONSTRAINT; Schema: public; Owner: root
--

ALTER TABLE ONLY public.place
    ADD CONSTRAINT pk_place PRIMARY KEY (id);


--
-- TOC entry 2303 (class 0 OID 0)
-- Dependencies: 7
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
GRANT ALL ON SCHEMA public TO PUBLIC;

SET search_path TO public; 

-- Completed on 2019-12-30 03:17:30 CET

--
-- PostgreSQL database dump complete
--


--
-- PostgreSQL database dump
--

-- Dumped from database version 16.10 (0374078)
-- Dumped by pg_dump version 17.5

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_log; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.audit_log (
    id integer NOT NULL,
    user_id integer,
    action character varying(100) NOT NULL,
    entity_type character varying(50) NOT NULL,
    entity_id integer,
    diff jsonb,
    correlation_id character varying(100),
    ip character varying(45),
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.audit_log OWNER TO neondb_owner;

--
-- Name: audit_log_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.audit_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_log_id_seq OWNER TO neondb_owner;

--
-- Name: audit_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.audit_log_id_seq OWNED BY public.audit_log.id;


--
-- Name: idempotency_keys; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.idempotency_keys (
    id integer NOT NULL,
    key_value character varying(100) NOT NULL,
    user_id integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    expires_at timestamp without time zone DEFAULT (now() + '01:00:00'::interval) NOT NULL
);


ALTER TABLE public.idempotency_keys OWNER TO neondb_owner;

--
-- Name: idempotency_keys_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.idempotency_keys_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.idempotency_keys_id_seq OWNER TO neondb_owner;

--
-- Name: idempotency_keys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.idempotency_keys_id_seq OWNED BY public.idempotency_keys.id;


--
-- Name: inventory_levels; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.inventory_levels (
    id integer NOT NULL,
    part_id integer NOT NULL,
    location_id integer NOT NULL,
    on_hand integer DEFAULT 0 NOT NULL,
    reserved integer DEFAULT 0 NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT inventory_levels_on_hand_check CHECK ((on_hand >= 0)),
    CONSTRAINT inventory_levels_reserved_check CHECK ((reserved >= 0))
);


ALTER TABLE public.inventory_levels OWNER TO neondb_owner;

--
-- Name: inventory_levels_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.inventory_levels_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inventory_levels_id_seq OWNER TO neondb_owner;

--
-- Name: inventory_levels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.inventory_levels_id_seq OWNED BY public.inventory_levels.id;


--
-- Name: inventory_moves; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.inventory_moves (
    id integer NOT NULL,
    part_id integer NOT NULL,
    location_id integer,
    qty integer NOT NULL,
    direction character varying(10) NOT NULL,
    reason character varying(20) NOT NULL,
    work_order_id integer,
    technician_id integer,
    supplier_id integer,
    price_at_tx numeric(10,2),
    currency character(3) DEFAULT 'USD'::bpchar,
    core_charge_at_tx numeric(10,2),
    core_rebate_expected numeric(10,2),
    core_rebate_received numeric(10,2),
    core_due_state character varying(20) DEFAULT 'none'::character varying,
    notes text,
    created_by integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT inventory_moves_core_due_state_check CHECK (((core_due_state)::text = ANY ((ARRAY['none'::character varying, 'due'::character varying, 'sent'::character varying, 'rebated'::character varying])::text[]))),
    CONSTRAINT inventory_moves_direction_check CHECK (((direction)::text = ANY ((ARRAY['in'::character varying, 'out'::character varying])::text[]))),
    CONSTRAINT inventory_moves_reason_check CHECK (((reason)::text = ANY ((ARRAY['receive'::character varying, 'checkout'::character varying, 'return'::character varying, 'core_return'::character varying, 'adjust'::character varying])::text[])))
);


ALTER TABLE public.inventory_moves OWNER TO neondb_owner;

--
-- Name: inventory_moves_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.inventory_moves_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inventory_moves_id_seq OWNER TO neondb_owner;

--
-- Name: inventory_moves_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.inventory_moves_id_seq OWNED BY public.inventory_moves.id;


--
-- Name: locations; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.locations (
    id integer NOT NULL,
    aisle character varying(20) NOT NULL,
    shelf character varying(20) NOT NULL,
    bay character varying(20) NOT NULL,
    bin character varying(20),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    deleted_at timestamp without time zone
);


ALTER TABLE public.locations OWNER TO neondb_owner;

--
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.locations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.locations_id_seq OWNER TO neondb_owner;

--
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- Name: part_numbers; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.part_numbers (
    id integer NOT NULL,
    part_id integer NOT NULL,
    value character varying(100) NOT NULL,
    type character varying(20) DEFAULT 'active'::character varying NOT NULL,
    manufacturer character varying(255),
    is_primary boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT part_numbers_type_check CHECK (((type)::text = ANY ((ARRAY['active'::character varying, 'historical'::character varying, 'aftermarket'::character varying])::text[])))
);


ALTER TABLE public.part_numbers OWNER TO neondb_owner;

--
-- Name: part_numbers_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.part_numbers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.part_numbers_id_seq OWNER TO neondb_owner;

--
-- Name: part_numbers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.part_numbers_id_seq OWNED BY public.part_numbers.id;


--
-- Name: part_suppliers; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.part_suppliers (
    id integer NOT NULL,
    part_id integer NOT NULL,
    supplier_id integer NOT NULL,
    sku character varying(100),
    price numeric(10,2) DEFAULT 0 NOT NULL,
    currency character(3) DEFAULT 'USD'::bpchar NOT NULL,
    core_charge numeric(10,2) DEFAULT 0 NOT NULL,
    expected_rebate numeric(10,2) DEFAULT 0 NOT NULL,
    recorded_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.part_suppliers OWNER TO neondb_owner;

--
-- Name: part_suppliers_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.part_suppliers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.part_suppliers_id_seq OWNER TO neondb_owner;

--
-- Name: part_suppliers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.part_suppliers_id_seq OWNED BY public.part_suppliers.id;


--
-- Name: parts; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.parts (
    id integer NOT NULL,
    anchor_slug character varying(100) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    notes text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    deleted_at timestamp without time zone
);


ALTER TABLE public.parts OWNER TO neondb_owner;

--
-- Name: parts_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.parts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.parts_id_seq OWNER TO neondb_owner;

--
-- Name: parts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.parts_id_seq OWNED BY public.parts.id;


--
-- Name: qr_codes; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.qr_codes (
    id integer NOT NULL,
    part_id integer NOT NULL,
    payload text NOT NULL,
    issued_at timestamp without time zone DEFAULT now() NOT NULL,
    expires_at timestamp without time zone
);


ALTER TABLE public.qr_codes OWNER TO neondb_owner;

--
-- Name: qr_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.qr_codes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.qr_codes_id_seq OWNER TO neondb_owner;

--
-- Name: qr_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.qr_codes_id_seq OWNED BY public.qr_codes.id;


--
-- Name: suppliers; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.suppliers (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    contact_email character varying(255),
    contact_phone character varying(50),
    reorder_url text,
    is_preferred boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    deleted_at timestamp without time zone
);


ALTER TABLE public.suppliers OWNER TO neondb_owner;

--
-- Name: suppliers_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.suppliers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.suppliers_id_seq OWNER TO neondb_owner;

--
-- Name: suppliers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.suppliers_id_seq OWNED BY public.suppliers.id;


--
-- Name: technicians; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.technicians (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    phone character varying(50),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    deleted_at timestamp without time zone
);


ALTER TABLE public.technicians OWNER TO neondb_owner;

--
-- Name: technicians_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.technicians_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.technicians_id_seq OWNER TO neondb_owner;

--
-- Name: technicians_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.technicians_id_seq OWNED BY public.technicians.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username character varying(100) NOT NULL,
    email character varying(255) NOT NULL,
    auth_source character varying(20) DEFAULT 'local'::character varying NOT NULL,
    password_hash character varying(255),
    role character varying(20) DEFAULT 'user'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    last_login_at timestamp without time zone,
    deleted_at timestamp without time zone,
    CONSTRAINT chk_auth_role_alignment CHECK (((((auth_source)::text = 'ldap'::text) AND ((role)::text = 'user'::text)) OR (((auth_source)::text = 'local'::text) AND ((role)::text = 'admin'::text)))),
    CONSTRAINT users_auth_source_check CHECK (((auth_source)::text = ANY ((ARRAY['ldap'::character varying, 'local'::character varying])::text[]))),
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['user'::character varying, 'admin'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO neondb_owner;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO neondb_owner;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: work_orders; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.work_orders (
    id integer NOT NULL,
    external_ref character varying(100) NOT NULL,
    vehicle_ref character varying(255),
    status character varying(20) DEFAULT 'open'::character varying NOT NULL,
    opened_at timestamp without time zone DEFAULT now() NOT NULL,
    closed_at timestamp without time zone,
    CONSTRAINT work_orders_status_check CHECK (((status)::text = ANY ((ARRAY['open'::character varying, 'closed'::character varying])::text[])))
);


ALTER TABLE public.work_orders OWNER TO neondb_owner;

--
-- Name: work_orders_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.work_orders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.work_orders_id_seq OWNER TO neondb_owner;

--
-- Name: work_orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.work_orders_id_seq OWNED BY public.work_orders.id;


--
-- Name: audit_log id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.audit_log ALTER COLUMN id SET DEFAULT nextval('public.audit_log_id_seq'::regclass);


--
-- Name: idempotency_keys id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.idempotency_keys ALTER COLUMN id SET DEFAULT nextval('public.idempotency_keys_id_seq'::regclass);


--
-- Name: inventory_levels id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_levels ALTER COLUMN id SET DEFAULT nextval('public.inventory_levels_id_seq'::regclass);


--
-- Name: inventory_moves id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves ALTER COLUMN id SET DEFAULT nextval('public.inventory_moves_id_seq'::regclass);


--
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- Name: part_numbers id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_numbers ALTER COLUMN id SET DEFAULT nextval('public.part_numbers_id_seq'::regclass);


--
-- Name: part_suppliers id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_suppliers ALTER COLUMN id SET DEFAULT nextval('public.part_suppliers_id_seq'::regclass);


--
-- Name: parts id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.parts ALTER COLUMN id SET DEFAULT nextval('public.parts_id_seq'::regclass);


--
-- Name: qr_codes id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.qr_codes ALTER COLUMN id SET DEFAULT nextval('public.qr_codes_id_seq'::regclass);


--
-- Name: suppliers id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.suppliers ALTER COLUMN id SET DEFAULT nextval('public.suppliers_id_seq'::regclass);


--
-- Name: technicians id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.technicians ALTER COLUMN id SET DEFAULT nextval('public.technicians_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: work_orders id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.work_orders ALTER COLUMN id SET DEFAULT nextval('public.work_orders_id_seq'::regclass);


--
-- Data for Name: audit_log; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.audit_log (id, user_id, action, entity_type, entity_id, diff, correlation_id, ip, created_at) FROM stdin;
\.


--
-- Data for Name: idempotency_keys; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.idempotency_keys (id, key_value, user_id, created_at, expires_at) FROM stdin;
\.


--
-- Data for Name: inventory_levels; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.inventory_levels (id, part_id, location_id, on_hand, reserved, created_at, updated_at) FROM stdin;
1	1	2	14	2	2025-12-01 22:15:03.941621	2025-12-01 22:15:03.941621
2	2	5	62	0	2025-12-01 22:15:03.941621	2025-12-01 22:15:03.941621
3	3	3	8	1	2025-12-01 22:15:03.941621	2025-12-01 22:15:03.941621
4	4	4	24	0	2025-12-01 22:15:03.941621	2025-12-01 22:15:03.941621
\.


--
-- Data for Name: inventory_moves; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.inventory_moves (id, part_id, location_id, qty, direction, reason, work_order_id, technician_id, supplier_id, price_at_tx, currency, core_charge_at_tx, core_rebate_expected, core_rebate_received, core_due_state, notes, created_by, created_at) FROM stdin;
\.


--
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.locations (id, aisle, shelf, bay, bin, is_active, created_at, deleted_at) FROM stdin;
1	A1	S1	B1	\N	t	2025-12-01 22:15:03.548009	\N
2	A1	S2	B3	\N	t	2025-12-01 22:15:03.548009	\N
3	A2	S1	B1	\N	t	2025-12-01 22:15:03.548009	\N
4	B1	S1	B1	BIN-1	t	2025-12-01 22:15:03.548009	\N
5	B4	S1	B1	BIN-12	t	2025-12-01 22:15:03.548009	\N
\.


--
-- Data for Name: part_numbers; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.part_numbers (id, part_id, value, type, manufacturer, is_primary, is_active, created_at) FROM stdin;
1	1	OEM-9981	active	OEMCo	t	t	2025-12-01 22:15:03.774648
2	1	ALT-8821	aftermarket	AfterParts	f	t	2025-12-01 22:15:03.774648
3	1	HIST-7701	historical	OEMCo	f	t	2025-12-01 22:15:03.774648
4	2	FIL-2201	active	OEMCo	t	t	2025-12-01 22:15:03.774648
5	2	AF-9921	aftermarket	AfterParts	f	t	2025-12-01 22:15:03.774648
6	3	ALT-5500	active	OEMCo	t	t	2025-12-01 22:15:03.774648
7	4	SPK-1200	active	OEMCo	t	t	2025-12-01 22:15:03.774648
\.


--
-- Data for Name: part_suppliers; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.part_suppliers (id, part_id, supplier_id, sku, price, currency, core_charge, expected_rebate, recorded_at) FROM stdin;
1	1	1	OEM-9981	125.00	USD	35.00	20.00	2025-12-01 22:15:03.864492
2	1	2	ALT-8821	89.99	USD	30.00	18.00	2025-12-01 22:15:03.864492
3	2	1	FIL-2201	24.99	USD	0.00	0.00	2025-12-01 22:15:03.864492
4	2	2	AF-9921	18.99	USD	0.00	0.00	2025-12-01 22:15:03.864492
5	3	1	ALT-5500	289.00	USD	75.00	50.00	2025-12-01 22:15:03.864492
6	4	3	SPK-1200	64.99	USD	0.00	0.00	2025-12-01 22:15:03.864492
\.


--
-- Data for Name: parts; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.parts (id, anchor_slug, name, description, notes, is_active, created_at, updated_at, deleted_at) FROM stdin;
1	brake-kit-001	Front Brake Kit	Front brake kit with pads and rotors	Fits 2015-2020 models. Store in climate-controlled area.	t	2025-12-01 22:15:03.699112	2025-12-01 22:15:03.699112	\N
2	filter-042	Oil Filter Premium	High-performance oil filter	Compatible with most domestic vehicles	t	2025-12-01 22:15:03.699112	2025-12-01 22:15:03.699112	\N
3	alternator-055	Heavy Duty Alternator	200A alternator for trucks	Core return required	t	2025-12-01 22:15:03.699112	2025-12-01 22:15:03.699112	\N
4	spark-plug-set-012	Platinum Spark Plug Set	Set of 8 platinum spark plugs	For V8 engines	t	2025-12-01 22:15:03.699112	2025-12-01 22:15:03.699112	\N
\.


--
-- Data for Name: qr_codes; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.qr_codes (id, part_id, payload, issued_at, expires_at) FROM stdin;
\.


--
-- Data for Name: suppliers; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.suppliers (id, name, contact_email, contact_phone, reorder_url, is_preferred, is_active, created_at, deleted_at) FROM stdin;
1	OEMCo	orders@oemco.com	555-0100	https://portal.oemco.com	t	t	2025-12-01 22:15:03.469801	\N
2	AfterParts	sales@afterparts.com	555-0200	https://afterparts.com/order	f	t	2025-12-01 22:15:03.469801	\N
3	QuickShip Auto	support@quickship.com	555-0300	https://quickship.com/b2b	t	t	2025-12-01 22:15:03.469801	\N
\.


--
-- Data for Name: technicians; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.technicians (id, name, email, phone, is_active, created_at, deleted_at) FROM stdin;
1	John Smith	jsmith@example.com	555-1001	t	2025-12-01 22:15:03.624	\N
2	Maria Garcia	mgarcia@example.com	555-1002	t	2025-12-01 22:15:03.624	\N
3	Tech Williams	twilliams@example.com	555-1003	t	2025-12-01 22:15:03.624	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.users (id, username, email, auth_source, password_hash, role, is_active, created_at, last_login_at, deleted_at) FROM stdin;
1	admin	admin@partops.local	local	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	admin	t	2025-12-01 22:15:02.696618	2025-12-02 03:04:43.445487	\N
\.


--
-- Data for Name: work_orders; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.work_orders (id, external_ref, vehicle_ref, status, opened_at, closed_at) FROM stdin;
\.


--
-- Name: audit_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.audit_log_id_seq', 1, false);


--
-- Name: idempotency_keys_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.idempotency_keys_id_seq', 1, false);


--
-- Name: inventory_levels_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.inventory_levels_id_seq', 4, true);


--
-- Name: inventory_moves_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.inventory_moves_id_seq', 1, false);


--
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.locations_id_seq', 5, true);


--
-- Name: part_numbers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.part_numbers_id_seq', 7, true);


--
-- Name: part_suppliers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.part_suppliers_id_seq', 6, true);


--
-- Name: parts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.parts_id_seq', 4, true);


--
-- Name: qr_codes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.qr_codes_id_seq', 1, false);


--
-- Name: suppliers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.suppliers_id_seq', 3, true);


--
-- Name: technicians_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.technicians_id_seq', 3, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: work_orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.work_orders_id_seq', 1, false);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (id);


--
-- Name: idempotency_keys idempotency_keys_key_value_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.idempotency_keys
    ADD CONSTRAINT idempotency_keys_key_value_key UNIQUE (key_value);


--
-- Name: idempotency_keys idempotency_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.idempotency_keys
    ADD CONSTRAINT idempotency_keys_pkey PRIMARY KEY (id);


--
-- Name: inventory_levels inventory_levels_part_id_location_id_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_levels
    ADD CONSTRAINT inventory_levels_part_id_location_id_key UNIQUE (part_id, location_id);


--
-- Name: inventory_levels inventory_levels_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_levels
    ADD CONSTRAINT inventory_levels_pkey PRIMARY KEY (id);


--
-- Name: inventory_moves inventory_moves_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves
    ADD CONSTRAINT inventory_moves_pkey PRIMARY KEY (id);


--
-- Name: locations locations_aisle_shelf_bay_bin_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_aisle_shelf_bay_bin_key UNIQUE (aisle, shelf, bay, bin);


--
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- Name: part_numbers part_numbers_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_numbers
    ADD CONSTRAINT part_numbers_pkey PRIMARY KEY (id);


--
-- Name: part_suppliers part_suppliers_part_id_supplier_id_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_suppliers
    ADD CONSTRAINT part_suppliers_part_id_supplier_id_key UNIQUE (part_id, supplier_id);


--
-- Name: part_suppliers part_suppliers_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_suppliers
    ADD CONSTRAINT part_suppliers_pkey PRIMARY KEY (id);


--
-- Name: parts parts_anchor_slug_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.parts
    ADD CONSTRAINT parts_anchor_slug_key UNIQUE (anchor_slug);


--
-- Name: parts parts_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.parts
    ADD CONSTRAINT parts_pkey PRIMARY KEY (id);


--
-- Name: qr_codes qr_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.qr_codes
    ADD CONSTRAINT qr_codes_pkey PRIMARY KEY (id);


--
-- Name: suppliers suppliers_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.suppliers
    ADD CONSTRAINT suppliers_pkey PRIMARY KEY (id);


--
-- Name: technicians technicians_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.technicians
    ADD CONSTRAINT technicians_pkey PRIMARY KEY (id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: work_orders work_orders_external_ref_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.work_orders
    ADD CONSTRAINT work_orders_external_ref_key UNIQUE (external_ref);


--
-- Name: work_orders work_orders_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.work_orders
    ADD CONSTRAINT work_orders_pkey PRIMARY KEY (id);


--
-- Name: idx_audit_log_created; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_audit_log_created ON public.audit_log USING btree (created_at);


--
-- Name: idx_audit_log_entity; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_audit_log_entity ON public.audit_log USING btree (entity_type, entity_id);


--
-- Name: idx_audit_log_user; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_audit_log_user ON public.audit_log USING btree (user_id);


--
-- Name: idx_idempotency_keys_expires; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_idempotency_keys_expires ON public.idempotency_keys USING btree (expires_at);


--
-- Name: idx_idempotency_keys_user; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_idempotency_keys_user ON public.idempotency_keys USING btree (user_id);


--
-- Name: idx_inventory_levels_location; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_inventory_levels_location ON public.inventory_levels USING btree (location_id);


--
-- Name: idx_inventory_levels_part; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_inventory_levels_part ON public.inventory_levels USING btree (part_id);


--
-- Name: idx_inventory_moves_core_state; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_inventory_moves_core_state ON public.inventory_moves USING btree (core_due_state);


--
-- Name: idx_inventory_moves_created; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_inventory_moves_created ON public.inventory_moves USING btree (created_at);


--
-- Name: idx_inventory_moves_part; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_inventory_moves_part ON public.inventory_moves USING btree (part_id);


--
-- Name: idx_inventory_moves_reason; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_inventory_moves_reason ON public.inventory_moves USING btree (reason);


--
-- Name: idx_part_numbers_fulltext; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_part_numbers_fulltext ON public.part_numbers USING gin (to_tsvector('english'::regconfig, (((value)::text || ' '::text) || (COALESCE(manufacturer, ''::character varying))::text)));


--
-- Name: idx_part_numbers_part_id; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_part_numbers_part_id ON public.part_numbers USING btree (part_id);


--
-- Name: idx_part_numbers_value; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_part_numbers_value ON public.part_numbers USING btree (value);


--
-- Name: idx_part_suppliers_part; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_part_suppliers_part ON public.part_suppliers USING btree (part_id);


--
-- Name: idx_part_suppliers_supplier; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_part_suppliers_supplier ON public.part_suppliers USING btree (supplier_id);


--
-- Name: idx_parts_anchor; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_parts_anchor ON public.parts USING btree (anchor_slug);


--
-- Name: idx_work_orders_ref; Type: INDEX; Schema: public; Owner: neondb_owner
--

CREATE INDEX idx_work_orders_ref ON public.work_orders USING btree (external_ref);


--
-- Name: audit_log audit_log_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.audit_log
    ADD CONSTRAINT audit_log_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: idempotency_keys idempotency_keys_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.idempotency_keys
    ADD CONSTRAINT idempotency_keys_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: inventory_levels inventory_levels_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_levels
    ADD CONSTRAINT inventory_levels_location_id_fkey FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE CASCADE;


--
-- Name: inventory_levels inventory_levels_part_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_levels
    ADD CONSTRAINT inventory_levels_part_id_fkey FOREIGN KEY (part_id) REFERENCES public.parts(id) ON DELETE CASCADE;


--
-- Name: inventory_moves inventory_moves_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves
    ADD CONSTRAINT inventory_moves_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: inventory_moves inventory_moves_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves
    ADD CONSTRAINT inventory_moves_location_id_fkey FOREIGN KEY (location_id) REFERENCES public.locations(id);


--
-- Name: inventory_moves inventory_moves_part_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves
    ADD CONSTRAINT inventory_moves_part_id_fkey FOREIGN KEY (part_id) REFERENCES public.parts(id) ON DELETE CASCADE;


--
-- Name: inventory_moves inventory_moves_supplier_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves
    ADD CONSTRAINT inventory_moves_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id);


--
-- Name: inventory_moves inventory_moves_technician_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves
    ADD CONSTRAINT inventory_moves_technician_id_fkey FOREIGN KEY (technician_id) REFERENCES public.technicians(id);


--
-- Name: inventory_moves inventory_moves_work_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.inventory_moves
    ADD CONSTRAINT inventory_moves_work_order_id_fkey FOREIGN KEY (work_order_id) REFERENCES public.work_orders(id);


--
-- Name: part_numbers part_numbers_part_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_numbers
    ADD CONSTRAINT part_numbers_part_id_fkey FOREIGN KEY (part_id) REFERENCES public.parts(id) ON DELETE CASCADE;


--
-- Name: part_suppliers part_suppliers_part_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_suppliers
    ADD CONSTRAINT part_suppliers_part_id_fkey FOREIGN KEY (part_id) REFERENCES public.parts(id) ON DELETE CASCADE;


--
-- Name: part_suppliers part_suppliers_supplier_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.part_suppliers
    ADD CONSTRAINT part_suppliers_supplier_id_fkey FOREIGN KEY (supplier_id) REFERENCES public.suppliers(id) ON DELETE CASCADE;


--
-- Name: qr_codes qr_codes_part_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.qr_codes
    ADD CONSTRAINT qr_codes_part_id_fkey FOREIGN KEY (part_id) REFERENCES public.parts(id) ON DELETE CASCADE;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: cloud_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE cloud_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO neon_superuser WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: cloud_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE cloud_admin IN SCHEMA public GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,TRUNCATE,UPDATE ON TABLES TO neon_superuser WITH GRANT OPTION;


--
-- PostgreSQL database dump complete
--


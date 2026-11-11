# EvaTrack — Entity-Relationship Diagram (ERD) Specification

**System Scope:** Barangay-based Evacuation Management & Tracking Platform (EvaTrack)  
**Reference Schema:** [`corrected_full_schema.sql`](file:///c:/CAPSTONE/EvaTrack/docs/corrected_full_schema.sql)  
**Notation Standard:** Classic Chen ER Model (Rectangles = Entities, Ovals = Attributes, Diamonds = Relationships, Cardinality Ratios = 1:1, 1:N, M:N)

---

## 1. ER Model Notation Conventions (Chen's Model)

| Component | Chen ER Graphical Symbol | Definition & Representation in Diagram |
| :--- | :---: | :--- |
| **Strong Entity** | **Single Rectangle** `[Entity]` | Independent existence; possesses an internal unique primary key. |
| **Weak Entity** | **Double Rectangle** `[[Entity]]` | Existence-dependent; relies on an identifying relationship with an owner entity. |
| **Primary Key Attribute** | **Oval with Underline** `([<u>PK</u>])` | Unique identifier attribute for an entity set. |
| **Simple Attribute** | **Standard Oval** `([Attribute])` | Atomic property or characteristic describing an entity. |
| **Derived Attribute** | **Dashed Oval** `([*Attribute*])` | Computed dynamically from other stored records (e.g., `current_occupancy`). |
| **Relationship** | **Diamond** `{Verb / Action}` | Association connecting two or more entities. |
| **Cardinality** | **Edge Label** `|1|`, `|N|`, `|M|` | Multiplicity constraint (One-to-One, One-to-Many, Many-to-Many). |

---

## 2. Global Core ERD (Chen Notation: Entities, Relationships & Cardinalities)

This diagram outlines the complete barangay evacuation domain: Strong & Weak entities connected through relationship diamonds with exact cardinality mappings.

```mermaid
flowchart TD

    %% ══════════════════════════════════════════════════════════════════════════
    %% STRONG ENTITIES (Single Border Rectangles)
    %% ══════════════════════════════════════════════════════════════════════════
    EC["🏢 EVACUATION_CENTERS\n(Strong Entity)"]:::strongEntity
    ROLE["🛡️ ROLES\n(Strong Entity)"]:::strongEntity
    USER["👤 USERS\n(Strong Entity)"]:::strongEntity
    ACTYPE["🏷️ ACCOMMODATION_TYPES\n(Strong Entity)"]:::strongEntity
    RRSTAT["📊 RESOURCE_REQUEST_STATUS\n(Strong Entity)"]:::strongEntity
    CICAT["🗂️ CENTER_ISSUE_CATEGORIES\n(Strong Entity)"]:::strongEntity
    CISTAT["📌 CENTER_ISSUE_REPORT_STATUSES\n(Strong Entity)"]:::strongEntity
    NOTIF["🔔 NOTIFICATIONS\n(Strong Entity)"]:::strongEntity

    %% ── External Partner Strong Entities (Bridges) ──
    HH["📋 HOUSEHOLDS\n(SafeTrack External Strong)"]:::extEntity
    EVT["⚡ DISASTER_EVENTS\n(ResQ External Strong)"]:::extEntity

    %% ══════════════════════════════════════════════════════════════════════════
    %% WEAK / ASSOCIATIVE ENTITIES (Double Border Rectangles)
    %% ══════════════════════════════════════════════════════════════════════════
    UNIT[["🚪 ACCOMMODATION_UNITS\n(Weak to Centers)"]]:::weakEntity
    ALLOC[["🛏️ UNIT_ALLOCATIONS\n(Associative Weak)"]]:::weakEntity
    EVAC[["📑 EVACUATION_RECORDS\n(Transactional Weak)"]]:::weakEntity
    MEM[["👥 EVACUATED_MEMBERS\n(Weak to Evac Record)"]]:::weakEntity
    ASSIGN[["📋 CENTER_PERSONNEL_ASSIGNMENTS\n(Associative Weak)"]]:::weakEntity
    RR[["📦 RESOURCE_REQUESTS\n(Weak to Centers/Users)"]]:::weakEntity
    CIR[["⚠️ CENTER_ISSUE_REPORTS\n(Weak to Centers/Users)"]]:::weakEntity
    RECP[["📨 NOTIFICATION_RECIPIENTS\n(Associative Weak)"]]:::weakEntity

    %% ══════════════════════════════════════════════════════════════════════════
    %% RELATIONSHIP DIAMONDS
    %% ══════════════════════════════════════════════════════════════════════════
    R_HOUSES{"Houses"}:::relDiamond
    R_ADMITS{"Admits"}:::relDiamond
    R_UNDERGOES{"Undergoes"}:::relDiamond
    R_TRIGGERS{"Triggers"}:::relDiamond
    R_INCLUDES{"Includes"}:::relDiamond
    R_ALLOCATES{"Allocates"}:::relDiamond
    R_CLASSIFIES{"Classifies"}:::relDiamond
    R_ASSIGNED{"Stationed_At"}:::relDiamond
    R_HAS_ROLE{"Has_Role"}:::relDiamond
    R_ORIGINATES{"Originates"}:::relDiamond
    R_REQUESTED_BY{"Drafted_By"}:::relDiamond
    R_RR_STATUS{"Triage_Status"}:::relDiamond
    R_LOGS{"Logs_Incident"}:::relDiamond
    R_REPORTED_BY{"Reported_By"}:::relDiamond
    R_ISSUE_CAT{"Categorized_By"}:::relDiamond
    R_ISSUE_STAT{"Resolution_Status"}:::relDiamond
    R_DISPATCHES{"Dispatched_To"}:::relDiamond
    R_SENT_BY{"Broadcast_By"}:::relDiamond

    %% ══════════════════════════════════════════════════════════════════════════
    %% CONNECTIONS & CARDINALITIES
    %% ══════════════════════════════════════════════════════════════════════════

    %% Evacuation Center -> Accommodation Units (1:N)
    EC ---|1| R_HOUSES
    R_HOUSES ---|N| UNIT

    %% Accommodation Types -> Units (1:N)
    ACTYPE ---|1| R_CLASSIFIES
    R_CLASSIFIES ---|N| UNIT

    %% Intake: Center -> Evacuation Records (1:N)
    EC ---|1| R_ADMITS
    R_ADMITS ---|N| EVAC

    %% Intake: Household -> Evacuation Records (1:N)
    HH ---|1| R_UNDERGOES
    R_UNDERGOES ---|N| EVAC

    %% Disaster Event -> Evacuation Records (1:N)
    EVT ---|1| R_TRIGGERS
    R_TRIGGERS ---|N| EVAC

    %% Evacuation Record -> Evacuated Members (1:N)
    EVAC ---|1| R_INCLUDES
    R_INCLUDES ---|N| MEM

    %% Room Allocation (Unit to Evacuation Record via Unit Allocation)
    UNIT ---|1| R_ALLOCATES
    R_ALLOCATES ---|N| ALLOC
    ALLOC ---|N| EVAC

    %% Personnel Station Assignment (Users <-> Centers via Assignments)
    USER ---|1| R_ASSIGNED
    R_ASSIGNED ---|N| ASSIGN
    ASSIGN ---|N| EC

    %% Roles -> Users (1:N)
    ROLE ---|1| R_HAS_ROLE
    R_HAS_ROLE ---|N| USER

    %% Resource Requests (Center + User -> Request -> Status)
    EC ---|1| R_ORIGINATES
    R_ORIGINATES ---|N| RR
    USER ---|1| R_REQUESTED_BY
    R_REQUESTED_BY ---|N| RR
    RR ---|N| R_RR_STATUS
    R_RR_STATUS ---|1| RRSTAT

    %% Center Issue Reports (Center + User -> Issue -> Category/Status)
    EC ---|1| R_LOGS
    R_LOGS ---|N| CIR
    USER ---|1| R_REPORTED_BY
    R_REPORTED_BY ---|N| CIR
    CIR ---|N| R_ISSUE_CAT
    R_ISSUE_CAT ---|1| CICAT
    CIR ---|N| R_ISSUE_STAT
    R_ISSUE_STAT ---|1| CISTAT

    %% Notifications (Dispatched to Households)
    USER ---|1| R_SENT_BY
    R_SENT_BY ---|N| NOTIF
    NOTIF ---|1| R_DISPATCHES
    R_DISPATCHES ---|N| RECP
    RECP ---|N| HH

    %% ══════════════════════════════════════════════════════════════════════════
    %% STYLING CLASSES
    %% ══════════════════════════════════════════════════════════════════════════
    classDef strongEntity fill:#1e3a8a,stroke:#3b82f6,stroke-width:2px,color:#fff,font-weight:bold
    classDef weakEntity   fill:#064e3b,stroke:#10b981,stroke-width:3px,stroke-dasharray: 2 2,color:#fff,font-weight:bold
    classDef extEntity    fill:#4c1d95,stroke:#8b5cf6,stroke-width:2px,color:#fff,font-weight:bold
    classDef relDiamond   fill:#78350f,stroke:#f59e0b,stroke-width:2px,color:#fff,font-weight:bold
```

---

## 3. Detailed Component Decomposition by Subsystem (Chen Model with Attribute Ovals)

Below, each subsystem is illustrated with its **Attribute Ovals** linked directly to the entity rectangles:
* `([<u>attribute</u>])` represents a **Primary Key**.
* `([attribute])` represents a **Simple / Secondary Attribute**.
* `([*attribute*])` represents a **Derived Attribute**.

---

### Subsystem A: Shelter Infrastructure & Room Allocations
Models evacuation center facilities, internal modular accommodation units, and living space allocations.

```mermaid
flowchart TD

    %% ── Entities ──
    EC["🏢 EVACUATION_CENTERS\n(Strong Entity)"]:::strongEntity
    UNIT[["🚪 ACCOMMODATION_UNITS\n(Weak Entity)"]]:::weakEntity
    ALLOC[["🛏️ UNIT_ALLOCATIONS\n(Weak Entity)"]]:::weakEntity

    %% ── Center Attributes (Ovals) ──
    A_EC_ID(["<u>evacuation_center_id</u>"]):::pkOval
    A_EC_NAME(["name"]):::attrOval
    A_EC_TYPE(["center_type"]):::attrOval
    A_EC_CAP(["capacity"]):::attrOval
    A_EC_OCC(["*current_occupancy*"]):::derivedOval
    A_EC_LAT(["latitude"]):::attrOval
    A_EC_LNG(["longitude"]):::attrOval
    A_EC_STAT(["status"]):::attrOval
    A_EC_CONT(["contact_person"]):::attrOval
    A_EC_NUM(["contact_number"]):::attrOval

    EC --- A_EC_ID
    EC --- A_EC_NAME
    EC --- A_EC_TYPE
    EC --- A_EC_CAP
    EC --- A_EC_OCC
    EC --- A_EC_LAT
    EC --- A_EC_LNG
    EC --- A_EC_STAT
    EC --- A_EC_CONT
    EC --- A_EC_NUM

    %% ── Unit Attributes (Ovals) ──
    A_U_ID(["<u>unit_id</u>"]):::pkOval
    A_U_NAME(["name"]):::attrOval
    A_U_CAP(["max_capacity"]):::attrOval

    UNIT --- A_U_ID
    UNIT --- A_U_NAME
    UNIT --- A_U_CAP

    %% ── Allocation Attributes (Ovals) ──
    A_AL_ID(["<u>allocation_id</u>"]):::pkOval
    A_AL_TIME(["created_at"]):::attrOval

    ALLOC --- A_AL_ID
    ALLOC --- A_AL_TIME

    %% ── Relationships ──
    R_HOUSES{"Houses"}:::relDiamond
    R_ALLOC{"Allocates_To"}:::relDiamond

    EC ---|1| R_HOUSES
    R_HOUSES ---|N| UNIT

    UNIT ---|1| R_ALLOC
    R_ALLOC ---|N| ALLOC

    classDef strongEntity fill:#1e3a8a,stroke:#3b82f6,stroke-width:2px,color:#fff,font-weight:bold
    classDef weakEntity   fill:#064e3b,stroke:#10b981,stroke-width:3px,color:#fff,font-weight:bold
    classDef relDiamond   fill:#78350f,stroke:#f59e0b,stroke-width:2px,color:#fff,font-weight:bold
    classDef pkOval       fill:#b91c1c,stroke:#ef4444,stroke-width:2px,color:#fff,font-weight:bold
    classDef attrOval     fill:#334155,stroke:#64748b,stroke-width:1.5px,color:#fff
    classDef derivedOval  fill:#0f766e,stroke:#14b8a6,stroke-width:1.5px,stroke-dasharray: 3 3,color:#fff
```

---

### Subsystem B: Evacuation Intake, Demographics & Admissions
Models resident arrival, verification method (`qr` vs. `manual`), and individual sheltered family members.

```mermaid
flowchart TD

    %% ── Entities ──
    EC["🏢 EVACUATION_CENTERS"]:::strongEntity
    HH["📋 HOUSEHOLDS\n(SafeTrack)"]:::extEntity
    EVAC[["📑 EVACUATION_RECORDS\n(Transactional Weak)"]]:::weakEntity
    MEM[["👥 EVACUATED_MEMBERS\n(Weak Entity)"]]:::weakEntity

    %% ── Evacuation Record Attributes ──
    A_ER_ID(["<u>evacuation_id</u>"]):::pkOval
    A_ER_COUNT(["evacuated_count"]):::attrOval
    A_ER_METH(["method\n('qr' / 'manual')"]):::attrOval
    A_ER_VER_AT(["verified_at"]):::attrOval

    EVAC --- A_ER_ID
    EVAC --- A_ER_COUNT
    EVAC --- A_ER_METH
    EVAC --- A_ER_VER_AT

    %% ── Evacuated Member Attributes ──
    A_EM_ID(["<u>evacuated_member_id</u>"]):::pkOval
    A_EM_TIME(["verified_at"]):::attrOval

    MEM --- A_EM_ID
    MEM --- A_EM_TIME

    %% ── Relationships ──
    R_ADMITS{"Admits"}:::relDiamond
    R_REG{"Enrolls"}:::relDiamond
    R_COMPRISES{"Comprises"}:::relDiamond

    EC ---|1| R_ADMITS
    R_ADMITS ---|N| EVAC

    HH ---|1| R_REG
    R_REG ---|N| EVAC

    EVAC ---|1| R_COMPRISES
    R_COMPRISES ---|N| MEM

    classDef strongEntity fill:#1e3a8a,stroke:#3b82f6,stroke-width:2px,color:#fff,font-weight:bold
    classDef weakEntity   fill:#064e3b,stroke:#10b981,stroke-width:3px,color:#fff,font-weight:bold
    classDef extEntity    fill:#4c1d95,stroke:#8b5cf6,stroke-width:2px,color:#fff,font-weight:bold
    classDef relDiamond   fill:#78350f,stroke:#f59e0b,stroke-width:2px,color:#fff,font-weight:bold
    classDef pkOval       fill:#b91c1c,stroke:#ef4444,stroke-width:2px,color:#fff,font-weight:bold
    classDef attrOval     fill:#334155,stroke:#64748b,stroke-width:1.5px,color:#fff
```

---

### Subsystem C: User Management, Roles & Station Assignments
Models the **Barangay IT / System Administrator (Super Admin)**, **Camp Commander (Evac Admin)**, and **Frontline Staff (Evac Personnel)** station assignments.

```mermaid
flowchart TD

    %% ── Entities ──
    ROLE["🛡️ ROLES\n(Strong Entity)"]:::strongEntity
    USER["👤 USERS\n(Strong Entity)"]:::strongEntity
    ASSIGN[["📋 CENTER_PERSONNEL_ASSIGNMENTS\n(Associative Weak)"]]:::weakEntity
    EC["🏢 EVACUATION_CENTERS\n(Strong Entity)"]:::strongEntity

    %% ── Role Attributes ──
    A_R_ID(["<u>role_id</u>"]):::pkOval
    A_R_KEY(["role_key\n('evac_admin','evac_personnel','super_admin')"]):::attrOval
    A_R_NAME(["role_name"]):::attrOval

    ROLE --- A_R_ID
    ROLE --- A_R_KEY
    ROLE --- A_R_NAME

    %% ── User Attributes ──
    A_U_ID(["<u>user_id</u>"]):::pkOval
    A_U_NAME(["name"]):::attrOval
    A_U_USER(["username"]):::attrOval
    A_U_EMAIL(["email"]):::attrOval
    A_U_PHONE(["contact_number"]):::attrOval
    A_U_ACT(["is_active"]):::attrOval

    USER --- A_U_ID
    USER --- A_U_NAME
    USER --- A_U_USER
    USER --- A_U_EMAIL
    USER --- A_U_PHONE
    USER --- A_U_ACT

    %% ── Assignment Attributes ──
    A_AS_ID(["<u>assignment_id</u>"]):::pkOval
    A_AS_ROLE(["role_at_center"]):::attrOval
    A_AS_STAT(["status\n('active','completed','transferred')"]):::attrOval
    A_AS_AT(["assigned_at"]):::attrOval
    A_AS_REL(["relieved_at"]):::attrOval

    ASSIGN --- A_AS_ID
    ASSIGN --- A_AS_ROLE
    ASSIGN --- A_AS_STAT
    ASSIGN --- A_AS_AT
    ASSIGN --- A_AS_REL

    %% ── Relationships ──
    R_HAS_ROLE{"Granted"}:::relDiamond
    R_STATION{"Stationed_To"}:::relDiamond
    R_AT_CENTER{"At_Shelter"}:::relDiamond

    ROLE ---|1| R_HAS_ROLE
    R_HAS_ROLE ---|N| USER

    USER ---|1| R_STATION
    R_STATION ---|N| ASSIGN

    ASSIGN ---|N| R_AT_CENTER
    R_AT_CENTER ---|1| EC

    classDef strongEntity fill:#1e3a8a,stroke:#3b82f6,stroke-width:2px,color:#fff,font-weight:bold
    classDef weakEntity   fill:#064e3b,stroke:#10b981,stroke-width:3px,color:#fff,font-weight:bold
    classDef relDiamond   fill:#78350f,stroke:#f59e0b,stroke-width:2px,color:#fff,font-weight:bold
    classDef pkOval       fill:#b91c1c,stroke:#ef4444,stroke-width:2px,color:#fff,font-weight:bold
    classDef attrOval     fill:#334155,stroke:#64748b,stroke-width:1.5px,color:#fff
```

---

### Subsystem D: Center Logistics & Issue Incident Reports
Models requisitions (food, water, medical kits) and facility/medical issues logged by camp staff.

```mermaid
flowchart TD

    %% ── Entities ──
    EC["🏢 EVACUATION_CENTERS"]:::strongEntity
    USER["👤 USERS"]:::strongEntity
    RR[["📦 RESOURCE_REQUESTS\n(Weak Entity)"]]:::weakEntity
    CIR[["⚠️ CENTER_ISSUE_REPORTS\n(Weak Entity)"]]:::weakEntity
    RR_STAT["📊 RESOURCE_REQUEST_STATUS"]:::strongEntity
    CI_CAT["🗂️ CENTER_ISSUE_CATEGORIES"]:::strongEntity

    %% ── Resource Request Attributes ──
    A_RR_ID(["<u>request_id</u>"]):::pkOval
    A_RR_TYPE(["resource_type"]):::attrOval
    A_RR_ITEM(["item_name"]):::attrOval
    A_RR_QTY(["quantity"]):::attrOval
    A_RR_UNIT(["unit"]):::attrOval
    A_RR_DESC(["description"]):::attrOval
    A_RR_REF(["tracking_reference"]):::attrOval

    RR --- A_RR_ID
    RR --- A_RR_TYPE
    RR --- A_RR_ITEM
    RR --- A_RR_QTY
    RR --- A_RR_UNIT
    RR --- A_RR_DESC
    RR --- A_RR_REF

    %% ── Issue Report Attributes ──
    A_CI_ID(["<u>report_id</u>"]):::pkOval
    A_CI_TITLE(["title"]):::attrOval
    A_CI_DESC(["description"]):::attrOval
    A_CI_SEV(["severity_id"]):::attrOval
    A_CI_ATT(["attachment_path"]):::attrOval

    CIR --- A_CI_ID
    CIR --- A_CI_TITLE
    CIR --- A_CI_DESC
    CIR --- A_CI_SEV
    CIR --- A_CI_ATT

    %% ── Relationships ──
    R_RQ_CTR{"Originates"}:::relDiamond
    R_RQ_USR{"Requested_By"}:::relDiamond
    R_RQ_ST{"Has_Status"}:::relDiamond

    R_CI_CTR{"Logs_Issue"}:::relDiamond
    R_CI_USR{"Reported_By"}:::relDiamond
    R_CI_CAT{"Classified_As"}:::relDiamond

    EC ---|1| R_RQ_CTR
    R_RQ_CTR ---|N| RR

    USER ---|1| R_RQ_USR
    R_RQ_USR ---|N| RR

    RR ---|N| R_RQ_ST
    R_RQ_ST ---|1| RR_STAT

    EC ---|1| R_CI_CTR
    R_CI_CTR ---|N| CIR

    USER ---|1| R_CI_USR
    R_CI_USR ---|N| CIR

    CIR ---|N| R_CI_CAT
    R_CI_CAT ---|1| CI_CAT

    classDef strongEntity fill:#1e3a8a,stroke:#3b82f6,stroke-width:2px,color:#fff,font-weight:bold
    classDef weakEntity   fill:#064e3b,stroke:#10b981,stroke-width:3px,color:#fff,font-weight:bold
    classDef relDiamond   fill:#78350f,stroke:#f59e0b,stroke-width:2px,color:#fff,font-weight:bold
    classDef pkOval       fill:#b91c1c,stroke:#ef4444,stroke-width:2px,color:#fff,font-weight:bold
    classDef attrOval     fill:#334155,stroke:#64748b,stroke-width:1.5px,color:#fff
```

---

## 4. Entity & Cardinality Summary Table

| Relationship (Diamond) | Entity 1 (Rectangle) | Entity 2 (Rectangle) | Cardinality Ratio | Participation Constraint | Description |
| :--- | :--- | :--- | :---: | :---: | :--- |
| **`Houses`** | `EVACUATION_CENTERS` | `ACCOMMODATION_UNITS` | **1 : N** | Mandatory (1) to Optional (N) | One center partitions into zero or many rooms/tents. |
| **`Classifies`** | `ACCOMMODATION_TYPES` | `ACCOMMODATION_UNITS` | **1 : N** | Optional (1) to Mandatory (N) | Room classification (classroom, tent, hall). |
| **`Allocates`** | `ACCOMMODATION_UNITS` | `UNIT_ALLOCATIONS` | **1 : N** | Optional (1) to Optional (N) | Living spaces allocated to admitted family units. |
| **`Admits`** | `EVACUATION_CENTERS` | `EVACUATION_RECORDS` | **1 : N** | Mandatory (1) to Optional (N) | Centers register incoming household check-ins. |
| **`Enrolls`** | `HOUSEHOLDS` *(SafeTrack)*| `EVACUATION_RECORDS` | **1 : N** | Mandatory (1) to Optional (N) | A household undergoes one or more evacuation stays. |
| **`Triggers`** | `DISASTER_EVENTS` *(ResQ)*| `EVACUATION_RECORDS` | **1 : N** | Optional (1) to Optional (N) | Evacuations linked to an active disaster incident. |
| **`Comprises`** | `EVACUATION_RECORDS` | `EVACUATED_MEMBERS` | **1 : N** | Mandatory (1) to Mandatory (N) | Each evacuation record lists individual verified members. |
| **`Stationed_At`** | `USERS` | `EVACUATION_CENTERS` | **M : N** (via `ASSIGNMENTS`) | Optional (M) to Optional (N) | Personnel scheduled to serve at specific evacuation centers. |
| **`Granted`** | `ROLES` | `USERS` | **1 : N** | Mandatory (1) to Mandatory (N) | Defines access tier (`super_admin`, `evac_admin`, `evac_personnel`). |
| **`Originates`** | `EVACUATION_CENTERS` | `RESOURCE_REQUESTS` | **1 : N** | Optional (1) to Optional (N) | Shelters submitting logistics/supply replenishment requests. |
| **`Logs_Incident`**| `EVACUATION_CENTERS` | `CENTER_ISSUE_REPORTS` | **1 : N** | Optional (1) to Optional (N) | Center incident tracking (sanitation, medical, security). |
| **`Dispatched_To`**| `NOTIFICATIONS` | `HOUSEHOLDS` | **M : N** (via `RECIPIENTS`) | Optional (M) to Optional (N) | Broadcast SMS and Push emergency alerts dispatched to residents. |


# EvaTrack – Data Flow Diagrams (DFD)

**Project Title:** EVATRACK: Streamlined Evacuation Processes and Coordination Platform Integrating SafeTrack Demographics and ResQperation Response Support
**Proponents:** Klint M. Ruales, Danica Gelbolingo, Anna Rhea Villadolid, Vhenz Cernal

Render these diagrams using [Mermaid Live Editor](https://mermaid.live).

---

## Level 0: Context Diagram

The Context Diagram shows the entire EvaTrack system as one process interacting with all external entities — internal users, external systems, and passive recipients.

```mermaid
%%{ init: { "flowchart": { "rankSpacing": 90, "nodeSpacing": 60 } } }%%
flowchart LR

    %% ── Internal Users (left side) ──
    SA["👤 Super Admin"]
    EA["👤 Evacuation Admin"]
    EP["👤 Evacuation Personnel"]

    %% ── Central System ──
    SYS(["🗂️ EvaTrack\nSystem"])

    %% ── External / Passive (right side) ──
    HH["📱 Households\n(Secondary)"]
    ST["🌐 SafeTrack\nSystem"]
    RQ["🌐 ResQperation\nSystem"]

    %% ── Super Admin ──
    SA -->|"Configs, users,\nevents, reports"| SYS
    SYS -->|"Reports, analytics,\noversight dashboard"| SA

    %% ── Evacuation Admin ──
    EA -->|"Center setup,\nalerts, QR scans"| SYS
    SYS -->|"Occupancy, roster,\nrequest status"| EA

    %% ── Evacuation Personnel ──
    EP -->|"QR scans, verifications,\nresource requests"| SYS
    SYS -->|"Verified members,\nevacuation status"| EP

    %% ── Households (passive — alerts only) ──
    SYS -->|"SMS alert"| HH
    SYS -->|"Push notification"| HH

    %% ── SafeTrack ──
    SYS <-->|"Household ID →\nDemographic record"| ST

    %% ── ResQperation ──
    SYS -->|"Resource / personnel\nrequest"| RQ
    SYS -->|"Push notification\ndispatch"| RQ

    %% ── Styles ──
    classDef userNode   fill:#1d4ed8,stroke:#1e40af,stroke-width:2px,color:#fff
    classDef sysNode    fill:#0f766e,stroke:#0d9488,stroke-width:3px,color:#fff,font-weight:bold
    classDef extNode    fill:#7c3aed,stroke:#6d28d9,stroke-width:2px,color:#fff
    classDef passNode   fill:#475569,stroke:#334155,stroke-width:2px,color:#fff

    class SA,EA,EP userNode
    class SYS sysNode
    class ST,RQ extNode
    class HH passNode
```

---

## Level 1: Data Flow Diagram

The Level 1 DFD breaks EvaTrack into 9 sub-processes grouped by functional area, showing data flows between actors, processes, data stores, and external systems.

```mermaid
%%{ init: { "flowchart": { "rankSpacing": 100, "nodeSpacing": 70 } } }%%
flowchart TB

    %% ════════════════════════════════════════
    %% EXTERNAL ENTITIES (top row)
    %% ════════════════════════════════════════
    SA["👤 Super Admin /\nEvacuation Admin"]
    EP["👤 Evacuation\nPersonnel"]
    HH["📱 Households\n(Secondary)"]
    ST["🌐 SafeTrack"]
    RQ["🌐 ResQperation"]

    %% ════════════════════════════════════════
    %% GROUP A — AUTH & ADMINISTRATION
    %% ════════════════════════════════════════
    subgraph GRP_A["🔐 Auth & Administration"]
        direction LR
        P1(["1.0\nAuth &\nUser Mgmt"])
        P2(["2.0\nCenter\nManagement"])
        P3(["3.0\nEvent\nManagement"])
    end

    %% ════════════════════════════════════════
    %% GROUP B — FIELD OPERATIONS
    %% ════════════════════════════════════════
    subgraph GRP_B["🚨 Field Operations"]
        direction LR
        P4(["4.0\nHousehold\nVerification"])
        P5(["5.0\nSafeTrack\nDemographics"])
        P6(["6.0\nEvacuation\nStatus & Occupancy"])
    end

    %% ════════════════════════════════════════
    %% GROUP C — COMMUNICATION & REQUESTS
    %% ════════════════════════════════════════
    subgraph GRP_C["📢 Communication & Requests"]
        direction LR
        P7(["7.0\nResource &\nRequest Routing"])
        P8(["8.0\nAlert &\nNotification Engine"])
    end

    %% ════════════════════════════════════════
    %% GROUP D — ANALYTICS
    %% ════════════════════════════════════════
    subgraph GRP_D["📊 Analytics"]
        direction LR
        P9(["9.0\nDemographic\nAnalytics"])
    end

    %% ════════════════════════════════════════
    %% DATA STORES (bottom row)
    %% ════════════════════════════════════════
    D1[(D1\nUsers)]
    D2[(D2\nHouseholds\n& Members)]
    D3[(D3\nCenters\n& Rooms)]
    D4[(D4\nDisaster\nEvents)]
    D5[(D5\nEvacuation\nRecords)]
    D6[(D6\nResource\nRequests)]
    D7[(D7\nNotifications\n& Logs)]
    D8[(D8\nAnalytics\nSnapshots)]

    %% ════════════════════════════════════════
    %% FLOWS — 1.0 Auth & User Mgmt
    %% ════════════════════════════════════════
    SA -->|"Credentials & role"| P1
    EP -->|"Credentials"| P1
    P1 <-->|"Read / write users"| D1
    P1 -->|"Auth token"| SA
    P1 -->|"Auth token"| EP

    %% ════════════════════════════════════════
    %% FLOWS — 2.0 Center Management
    %% ════════════════════════════════════════
    SA -->|"Center & room config"| P2
    P2 <-->|"Save / update centers"| D3
    P2 -->|"Center list & occupancy"| SA

    %% ════════════════════════════════════════
    %% FLOWS — 3.0 Event Management
    %% ════════════════════════════════════════
    SA -->|"Event details & severity"| P3
    P3 <-->|"Save / update events"| D4
    P3 -->|"Active event summary"| SA
    P3 -->|"Active event context"| EP

    %% ════════════════════════════════════════
    %% FLOWS — 4.0 Household Verification
    %% ════════════════════════════════════════
    EP -->|"QR scan or manual input"| P4
    SA -->|"QR scan or manual input"| P4
    P4 <-->|"Fetch household records"| D2
    P4 -->|"Request demographics"| P5
    P4 -->|"Verified member roster"| EP
    P4 -->|"Verified member roster"| SA
    P4 -->|"Trigger admission"| P6

    %% ════════════════════════════════════════
    %% FLOWS — 5.0 SafeTrack Demographics
    %% ════════════════════════════════════════
    P5 <-->|"Household ID →\nDemographic data"| ST
    P5 -->|"Demographics (personnel view)"| P4
    P5 -->|"Demographics for analytics"| P9

    %% ════════════════════════════════════════
    %% FLOWS — 6.0 Evacuation Status
    %% ════════════════════════════════════════
    P6 <-->|"Create / update records"| D5
    P6 <-->|"Update occupancy"| D3
    P6 -->|"Evacuation status update"| EP
    P6 -->|"Occupancy & roster"| SA
    P6 -->|"Admitted member data"| P9

    %% ════════════════════════════════════════
    %% FLOWS — 7.0 Resource Requests
    %% ════════════════════════════════════════
    EP -->|"Resource / personnel request"| P7
    SA -->|"Update request status"| P7
    P7 <-->|"Save / update requests"| D6
    P7 -->|"Routed request"| RQ
    RQ -->|"Acknowledgement"| P7
    P7 -->|"Request list & status"| SA

    %% ════════════════════════════════════════
    %% FLOWS — 8.0 Notification Engine
    %% ════════════════════════════════════════
    SA -->|"Alert message & recipients"| P8
    P8 <-->|"Fetch contacts & tokens"| D2
    P8 -->|"SMS alert"| HH
    P8 -->|"Push dispatch"| RQ
    RQ -->|"Delivery status"| P8
    P8 <-->|"Log delivery"| D7

    %% ════════════════════════════════════════
    %% FLOWS — 9.0 Analytics
    %% ════════════════════════════════════════
    D5 -->|"Evacuation records"| P9
    P9 <-->|"Store snapshots"| D8
    P9 -->|"Age, gender, PWD,\npregnant, total population"| SA

    %% ════════════════════════════════════════
    %% STYLES
    %% ════════════════════════════════════════
    classDef entity    fill:#1d4ed8,stroke:#1e40af,stroke-width:2px,color:#fff,font-weight:bold
    classDef process   fill:#ea580c,stroke:#c2410c,stroke-width:2px,color:#fff,font-weight:bold
    classDef datastore fill:#7c3aed,stroke:#6d28d9,stroke-width:2px,color:#fff
    classDef extNode   fill:#0f766e,stroke:#0d9488,stroke-width:2px,color:#fff,font-weight:bold
    classDef passNode  fill:#475569,stroke:#334155,stroke-width:2px,color:#fff

    class SA,EP entity
    class P1,P2,P3,P4,P5,P6,P7,P8,P9 process
    class D1,D2,D3,D4,D5,D6,D7,D8 datastore
    class ST,RQ extNode
    class HH passNode
```

---

## Process Descriptions

| # | Process | Description | Must-Have |
|---|---------|-------------|-----------|
| 1.0 | **Auth & User Mgmt** | Login, token issuance, user creation, role assignment, personnel-to-center assignment. | Role-based workflows |
| 2.0 | **Center Management** | Admin creates/configures evacuation centers and rooms; tracks real-time occupancy. | Auto capacity updates |
| 3.0 | **Event Management** | Admin creates and manages disaster events; links centers to active events. | Role-based workflows |
| 4.0 | **Household Verification** | Personnel verifies households via QR scan or manual input using SafeTrack demographics (hidden from households). | SafeTrack integration |
| 5.0 | **SafeTrack Demographics** | Fetches demographic data (age, gender, PWD, pregnancy) from SafeTrack for verification and analytics. Never exposed to households. | SafeTrack integration |
| 6.0 | **Evacuation Status & Occupancy** | Creates evacuation records, updates household status (`Evacuated` / `Not Evacuated`), auto-updates center occupancy on admission and checkout. | Status tracking, Auto capacity |
| 7.0 | **Resource & Request Routing** | Records in-center resource shortages and personnel requests; routes them to ResQperation. | Alert & assistance routing |
| 8.0 | **Alert & Notification Engine** | Sends evacuation alerts — SMS direct for no-internet households, push via ResQperation for app users. Logs all delivery. | Alert & assistance routing |
| 9.0 | **Demographic Analytics** | Aggregates demographic data from personnel updates: age distribution, pregnant count, PWD count, gender, total affected population. | Demographic analytics |

---

## Data Store Descriptions

| Store | Contents |
|-------|----------|
| **D1 Users** | System user accounts, roles, passwords, assigned centers. |
| **D2 Households & Members** | Household profiles, member demographics, contact numbers, device tokens. |
| **D3 Centers & Rooms** | Center configs, GPS, capacities, accommodation units, occupancy counts. |
| **D4 Disaster Events** | Event records, type, severity, assigned centers, active/ended status. |
| **D5 Evacuation Records** | Admission and checkout records, `Evacuated`/`Not Evacuated` status, member lists, timestamps. |
| **D6 Resource Requests** | Resource shortage and personnel request records, urgency, status, ResQperation reference ID. |
| **D7 Notifications & Logs** | Alert records, delivery logs, channel (SMS/push), delivery status per household. |
| **D8 Analytics Snapshots** | Computed analytics per event/center: age groups, gender, PWD, pregnant counts, totals. |

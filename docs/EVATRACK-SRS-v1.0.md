## UNIVERSITY OF SAN JOSE-RECOLETOS

School of Computer Studies
Cebu City, Philippines
# EVATRACK

## Streamlined Evacuation Processes and Coordination Platform Integrating SafeTrack Demographics and ResQperation Response Support

## Software Requirements Specification

### Version 1.0

A Capstone Project Presented to the Faculty of the
School of Computer Studies
University of San Jose-Recoletos
In Partial Fulfillment of the Requirements for
Bachelor of Science in Information Technology / Associate in Computer Technology
### Presented by:

Klint M. Ruales
Danica Gelbolingo
Anna Rhea Villadolid
Vhenz Cernal
Capstone Adviser: Mr. Stefan A.D. Flores
Project Coordinator: Mr. Roderick A. Bandalan
Panel Chair: Mr. Eric Magto  |  Panelist: Ms. Khiara Danielle Rubia
### October 2026


# Revision History


| Version | Date | Description | Author(s) |
| --- | --- | --- | --- |
| 0.1 | 2026-07-20 | Initial draft: project scope, must-haves elicitation, and stakeholder requirements analysis. | All proponents |
| 0.5 | 2026-08-20 | Complete functional and non-functional requirements, integration architecture (SafeTrack and ResQperation), adviser comments incorporated. | Ruales, Gelbolingo, Villadolid, Cernal |
| 1.0 | 2026-10-05 | Approved baseline for capstone panel defense incorporating verified backend actions, analytics computations, and field-tested UI workflows. | All proponents |

# Table of Contents

Revision History ........................................... 2
Table of Contents ......................................... 3
1. Introduction ............................................. 4
1.1 Purpose ............................................. 4
1.2 Scope ................................................. 4
1.3 Definitions, Acronyms, and Abbreviations .. 5
1.4 References ....................................... 6
1.5 Overview ........................................... 6
2. Overall Description ............................... 7
2.1 Product Perspective ..................... 7
2.2 Product Functions ......................... 8
2.3 User Classes and Characteristics .. 8
2.4 Operating Environment ................. 9
2.5 Design and Implementation Constraints .. 9
2.6 Assumptions and Dependencies ... 10
2.7 Apportioning of Requirements ... 10
3. Specific Requirements ........................... 11
3.1 External Interface Requirements .. 11
3.1.1 User Interfaces ................. 11
3.1.2 Hardware Interfaces ......... 11
3.1.3 Software Interfaces ......... 12
3.1.4 Communications Interfaces .. 12
3.2 Functional Requirements ............. 13
3.2.1 Authentication & Role-Based Access Control (AUTH) .. 13
3.2.2 SafeTrack Demographics Ingestion & Isolation (STK) .. 14
3.2.3 Evacuation Center & Room Configuration (CTR) .. 15
3.2.4 Household Verification & Admission Tracking (VER) .. 16
3.2.5 In-Center Stay, Room Assignment & Checkout (STAY) .. 17
3.2.6 Disaster Event Coordination (EVT) .. 18
3.2.7 In-Center Needs & Incident Reporting (ISS) .. 19
3.2.8 Resource Demands & ResQperation Response Routing (RES) .. 20
3.2.9 Emergency Dispatches, SMS & Push Broadcasts (NOTIF) .. 21
3.2.10 Evacuation Analytics & Demographic Reporting (ANL) .. 22
3.2.11 Audit Logging & Super Admin System Oversight (AUD) .. 23
3.3 Non-Functional Requirements ..... 24
3.3.1 Performance ......................... 24
3.3.2 Security & Data Confidentiality .. 24
3.3.3 Reliability and Disaster Availability .. 25
3.3.4 Usability & Field Accessibility .. 25
3.3.5 Maintainability & Clean Architecture .. 26
3.3.6 Portability ......................... 26
3.3.7 Data Privacy and Regulatory Compliance .. 26
3.4 Database Requirements ................. 27
Appendix A. Preliminary Entity-Relationship Overview .. 30
Appendix B. Requirements Traceability Matrix .. 31
Appendix C. Capstone Approval & Sign-Off Sheet .. 34

# 1. Introduction

## 1.1 Purpose

Purpose and Objective: This Software Requirements Specification (SRS) establishes the formal functional, behavioral, and non-functional baseline for the development and evaluation of EVATRACK (Version 1.0) — a Streamlined Evacuation Processes and Coordination Platform. It defines the complete scope of system capabilities, constraints, external interfaces, and security controls agreed upon between the student proponents, the capstone adviser, the project coordinator, the evaluation panel of the School of Computer Studies at the University of San Jose-Recoletos (USJ-R), and emergency response stakeholders.
The intended audiences of this document are: (a) the Capstone Evaluation Panel and Project Coordinator, to assess architectural rigor, functional completeness, and compliance with college project standards; (b) the Capstone Adviser, to guide iterative technical execution; (c) the Project Development Team, to translate every verifiable requirement into production-ready software artifacts, unit/feature tests, and deployment scripts; and (d) Disaster Risk Reduction and Management Officers (DRRMO), Barangay Disaster Committees, and evacuation center personnel, to validate that the operational workflows accurately mitigate field challenges encountered during natural disaster evacuations.
## 1.2 Scope

System Scope: EvaTrack is an enterprise-grade, web-based evacuation logistics, admission, and coordination management platform engineered to modernize disaster response within Local Government Units (LGUs). The system manages the full operational lifecycle of disaster evacuation across municipal shelters, comprising: multi-center registration and room-level capacity tracking; rapid evacuee household admission via QR code scanning and manual demographic search; in-center stay monitoring; emergency incident and facility health reporting; and resource demand management.
Crucially, EvaTrack establishes secure, bi-directional interoperability with two peer platforms in the disaster management ecosystem:
(1) SafeTrack Demographics System: EvaTrack retrieves pre-registered household master records and vulnerability profiles from SafeTrack. In strict adherence to data privacy principles, this demographic data is completely hidden from public/resident view and is exposed solely to authorized evacuation personnel on-site for identity verification, demographic aggregation, and needs prioritization.
(2) ResQperation Response Platform: EvaTrack routes resource supply requests, medical logistics packs, and search-and-rescue assistance directly to ResQperation coordinators, while utilizing ResQperation push gateways to broadcast real-time mobile evacuation warnings to citizens with smartphones.
User Roles & Access: The system enforces strict Role-Based Access Control (RBAC) across three primary operator tiers: Super Admin, Evacuation Admin, and Evacuation Personnel. Displaced households and citizen residents act as secondary beneficiaries who receive outbound multi-channel broadcasts (SMS and mobile push) and undergo on-site verification without holding direct interactive user accounts.
Scope Exclusions: Explicitly Out of Scope for Version 1.0:
• Long-term permanent post-disaster housing re-settlement administration;
• Direct financial cash-disbursement or commercial point-of-sale transactions;
• Wearable biometric health sensor telemetry;
• Native mobile app binary stores (EvaTrack provides a fully responsive PWA web interface accessed via desktop, tablet, and mobile browsers).
## 1.3 Definitions, Acronyms, and Abbreviations


| Term / Acronym | Definition |
| --- | --- |
| EvaTrack | Streamlined Evacuation Processes and Coordination Platform specified in this document. |
| SafeTrack | Municipal census and household demographic profiling system serving as the authoritative demographic data source. |
| ResQperation | Emergency rescue dispatch and response coordination platform integrated with EvaTrack for logistics routing and push notifications. |
| SRS | Software Requirements Specification — this document, conforming to IEEE Std 830-1998 and ISO/IEC/IEEE 29148:2018. |
| RBAC | Role-Based Access Control — security architecture restricting system operations according to authenticated user roles. |
| LGU | Local Government Unit (Municipality, City, or Barangay in the Republic of the Philippines). |
| DRRMO / MDRRMO | Disaster Risk Reduction and Management Office / Municipal DRRMO. |
| NDRRMC | National Disaster Risk Reduction and Management Council of the Philippines. |
| DPA of 2012 | Republic Act No. 10173 (Data Privacy Act of 2012, Philippines) and its Implementing Rules and Regulations. |
| PWD | Person with Disability — tracked as a vulnerable demographic category for priority evacuation accommodation. |
| QR Code | Quick Response Matrix Barcode — encoded with a household's unique verification token for rapid contactless check-in. |
| SMS | Short Message Service — utilized for dispatching cellular emergency advisories to residents without active mobile data. |
| REST API | Representational State Transfer Application Programming Interface — JSON-based client-server communication architecture. |
| DTO | Data Transfer Object — typed domain structure encapsulating payload parameters across architectural layers. |
| ORM | Object-Relational Mapping — Eloquent abstraction layer mapping MySQL database tables to PHP domain models. |
| CRUD | Create, Read, Update, Delete — fundamental persistent data operations executed by the backend repository layer. |

## 1.4 References

- ISO/IEC/IEEE 29148:2018: Systems and software engineering — Life cycle processes — Requirements engineering.
- IEEE Std 830-1998: IEEE Recommended Practice for Software Requirements Specifications (structural reference).
- Republic Act No. 10173: Data Privacy Act of 2012, National Privacy Commission, Republic of the Philippines.
- Joint Memorandum Circular No. 1 (2013): Guidelines on Evacuation Center Coordination and Management, NDRRMC / DSWD / DOH.
- Laravel Framework Reference: Laravel 11.x/12.x Official Documentation, Laravel LLC, https://laravel.com/docs.
- React Library Reference: React 18.x Documentation & Component API, Meta Platforms, Inc., https://react.dev.
- MySQL 8.0 Engine Reference: MySQL 8.0 Reference Manual, Oracle Corporation, https://dev.mysql.com/doc/.
- EVATRACK-Must-Haves (1).docx: EvaTrack Capstone Baseline Specification, School of Computer Studies, USJ-R.
## 1.5 Overview

The remainder of this document is structured as follows: Section 2 presents an overall description of EvaTrack, covering its operational context, high-level product functions, user classes, operating environment, design constraints, and dependencies. Section 3 details the specific requirements, including external interfaces (UI, hardware, software, communications), comprehensive functional requirements organized by domain subsystems, non-functional quality attributes, and the database schema dictionary. Appendices A, B, and C provide the preliminary Entity-Relationship narrative, the Must-Haves Requirements Traceability Matrix, and the Capstone Panel Approval Sheet.

# 2. Overall Description

## 2.1 Product Perspective

System Architecture: EvaTrack operates as a modern, decoupled web application built on Domain-Driven Design (DDD) principles. It bridges municipal disaster administration and field center operations through a three-tier architecture:
(a) Presentation Tier: A responsive Single-Page Application (SPA) built using React 18, Vite, and Tailwind CSS. The interface provides accessible, high-contrast light and dark modes, intuitive tabular data tables, real-time KPI progress cards, camera-driven HTML5 QR scanning, and interactive analytics visualizations powered by Recharts.
(b) Application & Business Logic Tier: A robust Laravel backend architected using Action-Domain-Responder (ADR) and Service-Repository patterns. Each functional capability is encapsulated in discrete Action classes (e.g., SearchHouseholdsAction, UpdateEvacuatedMemberStatusAction, CreateResourceRequestAction) with typed DTOs and Form Request validation.
(c) Persistence Tier: A MySQL 8.0 relational database operating with InnoDB engine, utf8mb4 encoding, strict foreign key constraints, and soft deletion flags to maintain forensic audit trails.
Ecosystem Placement: System Interoperability Context:
EvaTrack interfaces with SafeTrack via private authenticated API channels to ingest household rosters and vulnerability markers. SafeTrack data remains confidential to system operators. Concurrently, EvaTrack communicates with ResQperation via outbound webhooks and REST endpoints to transfer resource requests and trigger push notification delivery to registered resident devices.
## 2.2 Product Functions

At a macro level, EvaTrack fulfills the following primary functions:
- Demographic Ingestion: Ingest and index master citizen records from SafeTrack, restricting visibility solely to verification personnel.
- Household Verification: Rapid admission of arriving families using camera-based QR code scanning or indexed multi-criteria search (name, contact, address).
- Status Tracking: Real-time state tracking of households and individual family members as Evacuated or Not Evacuated.
- Capacity Recalculation: Dynamic adjustment of center-level and room-level capacity counts immediately upon household admission or checkout.
- Shelter & Room Management: Multi-center operational control, room zoning, and designated personnel assignment.
- Resource Demand Routing: Logging of shelter shortages (food, water, medicines) and dispatching resource assistance tickets directly to ResQperation.
- In-Center Incident Reporting: Logging and lifecycle resolution tracking of shelter facility damage, sanitation issues, and medical emergencies.
- Emergency Alert Dispatch: Multi-channel broadcast transmission (SMS via GSM gateway and push notifications via ResQperation).
- Evacuation Analytics: Real-time computation of demographic vulnerabilities (age cohorts, pregnant women, PWDs, sex breakdown, total affected persons).
- Oversight & Audit: Immutable audit logging of operator actions with emergency override capabilities for system recovery.
## 2.3 User Classes and Characteristics


| User Class | Operational Responsibilities & Scope | Proficiency & Access Level |
| --- | --- | --- |
| Super Admin | Maintains supreme oversight across all centers, users, and disaster events. Generates system-wide analytics, exports consolidated municipality reports, and holds emergency override authority across all administrative actions for troubleshooting. | High technical proficiency; Full root system access. |
| Evacuation Admin | Creates and configures evacuation centers and sub-rooms. Assigns evacuation personnel to specific shelters. Monitors cross-center occupancy, active resource requests, and issue reports. Authorizes and dispatches emergency broadcasts via SMS and ResQperation push. | Moderate; Trained disaster operation manager. |
| Evacuation Personnel | Stationed on-site at assigned evacuation centers. Performs QR scanning and manual search verification of arriving evacuees. Updates household stay details, assigns families to available rooms, logs facility needs/shortages, and submits assistance requests. | Basic to moderate; Field staff trained in mobile/tablet scanner operation. |
| Households / Residents<br>(Secondary Actor) | Displaced citizens who receive emergency broadcast warnings via SMS or ResQperation mobile app. Present physical or digital QR codes upon center arrival. Do not possess interactive login credentials. | General public; Zero technical training required. |
| SafeTrack Platform<br>(External Actor) | Authoritative municipal demographic database. Supplies verified household data, member rosters, and vulnerability tags to EvaTrack via automated API synchronization. | Machine-to-machine authenticated REST client. |
| ResQperation System<br>(External Actor) | External disaster response platform. Ingests escalated resource/personnel tickets from EvaTrack and routes mobile push broadcasts to citizens. | Machine-to-machine authenticated REST / Webhook client. |

## 2.4 Operating Environment

- Server Infrastructure: Ubuntu 22.04 / 24.04 LTS, Nginx 1.24+, PHP 8.2+ with PHP-FPM, Composer 2.x, MySQL 8.0 Enterprise/Community Server.
- Client Platforms: Modern evergreen browsers (Google Chrome 115+, Microsoft Edge 115+, Mozilla Firefox 115+, Safari 16+) on desktop, laptop, tablet, or smartphone viewports (360px to 4K).
- Hardware & Peripherals: Built-in or USB webcams, smartphone cameras supporting MediaDevices.getUserMedia() for real-time QR scanning; standard office printers for PDF export printing.
- Network Environment: Standard TCP/IP with minimum 256 kbps bandwidth for field personnel; HTTPS (TLS 1.2/1.3); cellular GSM connectivity for SMS dispatch gateways.
## 2.5 Design and Implementation Constraints

- Backend Architectural Constraint: The backend must strictly utilize Laravel 11/12 with PHP 8.2+ following Domain-Driven Design (DDD) with discrete Action, DTO, and Repository layers.
- Frontend Architectural Constraint: The frontend must be implemented in React 18 with Vite build tooling and Tailwind CSS utility styling for accessible dark/light presentation.
- Database Security Constraint: Every query execution must utilize Eloquent ORM or parameterized query binding; string concatenation in SQL queries is prohibited.
- Privacy & Statutory Constraint: In accordance with RA 10173, SafeTrack demographic data must never be published, displayed on public portals, or accessed by unauthorized roles.
- Version Control Constraint: All database schema changes must be codified as reversible Laravel migrations version-controlled within Git.
## 2.6 Assumptions and Dependencies

- Operational Assumption: Municipal LGUs designate official evacuation centers and appoint certified Evacuation Admins and Personnel prior to disaster onset.
- Data Dependency: SafeTrack demographic registries are populated with accurate citizen rosters prior to disaster event declaration.
- Integration Dependency: ResQperation server endpoints remain available to accept logistics requests and deliver push messages during active emergencies.
- Telecommunications Dependency: Cellular telco carriers (Smart, Globe, DITO) maintain functional SMS gateway relay capability during evacuation advisories.
## 2.7 Apportioning of Requirements

All requirements cataloged under the 'Must' priority in Section 3 represent the non-negotiable core must-haves baseline specified in EVATRACK-Must-Haves (1).docx and verified for the panel defense. 'Should' priority requirements represent operational enhancements implemented to optimize usability and field resilience. 'Could' priority items represent exploratory extensions reserved for post-capstone production iterations.

# 3. Specific Requirements

## 3.1 External Interface Requirements

### 3.1.1 User Interfaces

- UI-01 Responsive Layout: The system shall deliver a fully responsive, mobile-first web interface rendered via React and Tailwind CSS, supporting display resolutions from 360x640 up to 3840x2160.
- UI-02 Theme Adaptation: The user interface shall support instantaneous, flash-free toggling between dark mode and light mode, persisting preference in localStorage.
- UI-03 Emergency Announcement Banner: The dashboard shall display an uncluttered emergency announcement banner indicating the active disaster event name, type, and severity with manual pagination controls.
- UI-04 Tabular Interfaces: All tabular lists (Households, Evacuees, Centers, Issues, Requests) shall provide real-time search filtering, column sort toggles, status pills, and accessible pagination.
- UI-05 QR Scanner Viewfinder: The verification workflow shall provide a high-performance camera viewfinder modal with real-time target framing, automatic camera switching, and immediate audio-visual scan feedback.
### 3.1.2 Hardware Interfaces

- HW-01 Camera Interface: Standard integrated or external USB/Bluetooth video capture devices adhering to WebRTC / MediaDevices APIs for optical QR code scanning.
- HW-02 Print Interface: Standard operating system print subsystem (CUPS, Windows Spooler) accessible via window.print() for generating paper evacuation manifests and CSV logs.
### 3.1.3 Software Interfaces


| Interface Name | Functional Scope & Purpose | Protocol & Integration Mechanism |
| --- | --- | --- |
| SafeTrack Demographics API | Retrieves household profiles, family member rosters, addresses, and vulnerability categories (infants, elderly, pregnant, PWDs). | REST / JSON over HTTPS; Bearer API Token; Local database replication sync. |
| ResQperation Push Gateway | Transmits urgent disaster warnings and push notifications to registered residents holding the ResQperation mobile app. | Outbound REST API / JSON over HTTPS; Asynchronous queue worker. |
| ResQperation Logistics Dispatch | Escalates center supply demands (food, water, medicine, personnel) to ResQperation response coordinators. | REST API / Webhook payload containing itemized requisition DTOs. |
| Cellular SMS Gateway | Transmits SMS emergency evacuation alerts and verification acknowledgments to residents lacking internet connectivity. | HTTPS REST / Semaphore / Twilio SMS Gateway; Asynchronous job queue. |

### 3.1.4 Communications Interfaces

- COM-01 Transport Encryption: All browser-to-server and server-to-server exchanges shall use HTTPS with TLS 1.2/1.3 encryption.
- COM-02 Token Security: API sessions shall authenticate via Laravel Sanctum bearer tokens with configurable expiration and automatic revocation upon logout.
- COM-03 Request Integrity: All state-modifying requests shall be protected against Cross-Site Request Forgery (CSRF) and Cross-Origin Resource Sharing (CORS) whitelisting.
## 3.2 Functional Requirements

Functional requirements are cataloged below by operational domain. Each requirement is expressed as a verifiable 'shall' statement, assigned a unique identifier, categorized by MoSCoW priority (Must, Should, Could), and explicitly attributed to the responsible proponent and must-haves baseline from EVATRACK-Must-Haves (1).docx.
### 3.2.1 Authentication & Role-Based Access Control (AUTH)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-AUTH-01 | The system shall authenticate operators using unique email/user ID credentials and bcrypt password hashes before granting access. | Must | Anna Rhea Villadolid |
| FR-AUTH-02 | The system shall enforce Role-Based Access Control (RBAC) across Super Admin, Evacuation Admin, and Evacuation Personnel roles via backend route middleware. | Must | Anna Rhea Villadolid |
| FR-AUTH-03 | The system shall restrict Evacuation Personnel actions strictly to their assigned evacuation center, denying modification of external centers. | Must | Anna Rhea Villadolid |
| FR-AUTH-04 | The system shall allow Super Admins and Evacuation Admins to create, update, activate, and deactivate personnel accounts with center assignment. | Must | Anna Rhea Villadolid |
| FR-AUTH-05 | The system shall invalidate user authorization tokens immediately upon logout and enforce session timeout after 30 minutes of inactivity. | Must | Anna Rhea Villadolid |
| FR-AUTH-06 | The system shall allow authenticated operators to update their profile information and change passwords with current-password confirmation. | Should | Anna Rhea Villadolid |

### 3.2.2 SafeTrack Demographics Ingestion & Isolation (STK)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-STK-01 | The system shall ingest household demographic data from SafeTrack, including head of household, member roster, birthdates, gender, and address. | Must | Vhenz Cernal |
| FR-STK-02 | The system shall capture vulnerable group classifications from SafeTrack (infants, children, elderly, pregnant women, and PWDs). | Must | Vhenz Cernal |
| FR-STK-03 | The system shall strictly restrict the visibility of SafeTrack demographic data to authorized evacuation personnel and admins during verification. | Must | Vhenz Cernal |
| FR-STK-04 | The system shall prohibit direct public and resident access to raw SafeTrack demographic records to ensure compliance with RA 10173. | Must | Vhenz Cernal |
| FR-STK-05 | The system shall support incremental re-synchronization of SafeTrack demographic datasets without overwriting active evacuation stay logs. | Should | Vhenz Cernal |

### 3.2.3 Evacuation Center & Room Configuration (CTR)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-CTR-01 | The system shall allow Evacuation Admins and Super Admins to create and configure evacuation centers with name, address coordinates, and capacity. | Must | Vhenz Cernal |
| FR-CTR-02 | The system shall allow Evacuation Admins to configure individual rooms and partitions within an evacuation center, specifying room capacity and gender allocation. | Must | Danica Gelbolingo |
| FR-CTR-03 | The system shall automatically recalculate total center capacity and room occupancy upon household verification and room assignment. | Must | Vhenz Cernal |
| FR-CTR-04 | The system shall display real-time occupancy percentage badges and progress meters indicating available, near-capacity, and full shelter status. | Must | Vhenz Cernal |
| FR-CTR-05 | The system shall allow Evacuation Admins to assign and re-assign Evacuation Personnel to operational evacuation centers. | Must | Danica Gelbolingo |
| FR-CTR-06 | The system shall prevent admitting households to centers that have reached 100% capacity unless an administrative override is authorized. | Should | Vhenz Cernal |

### 3.2.4 Household Verification & Admission Tracking (VER)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-VER-01 | The system shall enable Evacuation Personnel to verify arriving households via camera-driven optical scanning of digital or printed QR codes. | Must | Klint M. Ruales |
| FR-VER-02 | The system shall provide fallback manual verification by searching SafeTrack records using household head name, member name, or contact number. | Must | Klint M. Ruales |
| FR-VER-03 | The system shall display the household member roster upon successful verification, allowing personnel to select which members are physically present. | Must | Klint M. Ruales |
| FR-VER-04 | The system shall update household evacuation status to 'Evacuated' once verified members are admitted to the center. | Must | Vhenz Cernal |
| FR-VER-05 | The system shall automatically lock the 'Admit' button and display an 'Admitted' badge when a household reaches 100% verified member admission. | Must | Klint M. Ruales |
| FR-VER-06 | The system shall record verification timestamps, admitting personnel ID, and admission mode (QR vs. Manual) in the persistent evacuation record. | Must | Klint M. Ruales |
| FR-VER-07 | The system shall alert personnel if a household has already been admitted to another center to prevent fraudulent duplicate registrations. | Must | Klint M. Ruales |

### 3.2.5 In-Center Stay, Room Assignment & Checkout (STAY)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-STAY-01 | The system shall allow Evacuation Personnel to assign admitted households and family members to designated rooms within the center. | Must | Danica Gelbolingo |
| FR-STAY-02 | The system shall monitor household stay duration, displaying admission timestamp and real-time elapsed hours/days in shelter. | Must | Danica Gelbolingo |
| FR-STAY-03 | The system shall allow personnel to update individual member statuses (e.g., in-shelter, referred to hospital, temporary leave). | Must | Danica Gelbolingo |
| FR-STAY-04 | The system shall allow Evacuation Personnel to process household checkout upon official evacuation decampment. | Must | Danica Gelbolingo |
| FR-STAY-05 | The system shall automatically deduct departing household member counts from center and room occupancy tallies upon checkout. | Must | Vhenz Cernal |
| FR-STAY-06 | The system shall archive completed evacuation records permanently for historical analytics while updating active status to 'Decamped'. | Must | Danica Gelbolingo |

### 3.2.6 Disaster Event Coordination (EVT)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-EVT-01 | The system shall allow Evacuation Admins and Super Admins to declare and manage disaster events with event name, disaster type, and severity. | Must | Vhenz Cernal |
| FR-EVT-02 | The system shall automatically link operational evacuation centers to active disaster declarations across the municipality. | Must | Vhenz Cernal |
| FR-EVT-03 | The system shall display active disaster announcements prominently on the operator dashboard without distracting marquee animation. | Must | Klint M. Ruales |
| FR-EVT-04 | The system shall allow Evacuation Admins to conclude active disaster events, archiving event-scoped evacuation metrics. | Must | Vhenz Cernal |
| FR-EVT-05 | The system shall track historical disaster events, allowing comparative analytics across past typhoons, floods, and earthquakes. | Should | Klint M. Ruales |

### 3.2.7 In-Center Needs & Incident Reporting (ISS)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-ISS-01 | The system shall allow Evacuation Personnel to log in-center issue reports categorized by Facility Damage, Sanitation, Health, or Security. | Must | Anna Rhea Villadolid |
| FR-ISS-02 | The system shall capture issue severity levels (Low, Medium, High, Critical) and incident descriptions for administrative triage. | Must | Anna Rhea Villadolid |
| FR-ISS-03 | The system shall enable Evacuation Admins to review, acknowledge, assign, and resolve center issue reports. | Must | Anna Rhea Villadolid |
| FR-ISS-04 | The system shall maintain an audit history of issue status transitions (Open -> In Progress -> Resolved -> Closed). | Must | Anna Rhea Villadolid |
| FR-ISS-05 | The system shall display center issue distribution charts and resolution rate KPIs on the administrative analytics dashboard. | Must | Klint M. Ruales |

### 3.2.8 Resource Demands & ResQperation Response Routing (RES)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-RES-01 | The system shall allow Evacuation Personnel to submit resource supply requests specifying resource type, quantity demanded, and urgency. | Must | Anna Rhea Villadolid |
| FR-RES-02 | The system shall allow Evacuation Personnel to request additional response personnel and specialized rescue support from ResQperation. | Must | Anna Rhea Villadolid |
| FR-RES-03 | The system shall route resource requests to Evacuation Admins and transmit external assistance payloads to ResQperation coordinators. | Must | Anna Rhea Villadolid |
| FR-RES-04 | The system shall track resource fulfillment lifecycle across Pending, Approved, In Transit, Delivered, and Rejected states. | Must | Anna Rhea Villadolid |
| FR-RES-05 | The system shall visualize top requested supply categories and volumes in a proportional 3-column progress chart with right-aligned units. | Must | Klint M. Ruales |
| FR-RES-06 | The system shall compute overall resource fulfillment rates per center and system-wide. | Must | Klint M. Ruales |

### 3.2.9 Emergency Dispatches, SMS & Push Broadcasts (NOTIF)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-NOTIF-01 | The system shall provide an SMS & Push dispatch interface for Evacuation Admins to compose and transmit urgent public evacuation advisories. | Must | Klint M. Ruales |
| FR-NOTIF-02 | The system shall route SMS alerts to registered household contact numbers lacking active mobile data via cellular SMS gateway. | Must | Klint M. Ruales |
| FR-NOTIF-03 | The system shall route push notification advisories to residents holding the ResQperation mobile application. | Must | Klint M. Ruales |
| FR-NOTIF-04 | The system shall allow geographic targeting of advisories by municipality, barangay, or specific evacuation zone. | Must | Klint M. Ruales |
| FR-NOTIF-05 | The system shall track transmission dispatch logs, recipient counts, and delivery success/failure statuses. | Must | Klint M. Ruales |
| FR-NOTIF-06 | The system shall export broadcast history logs to CSV for post-disaster audit reporting. | Should | Klint M. Ruales |

### 3.2.10 Evacuation Analytics & Demographic Reporting (ANL)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-ANL-01 | The system shall dynamically aggregate evacuation demographic analytics from verified admission records. | Must | Klint M. Ruales |
| FR-ANL-02 | The system shall compute age group distributions (infants 0-2, children 3-12, adolescents 13-17, adults 18-59, elderly 60+). | Must | Klint M. Ruales |
| FR-ANL-03 | The system shall compute total counts and percentages of pregnant women and nursing mothers admitted to evacuation centers. | Must | Klint M. Ruales |
| FR-ANL-04 | The system shall compute total counts of Persons with Disabilities (PWDs) and individuals requiring critical medical assistance. | Must | Klint M. Ruales |
| FR-ANL-05 | The system shall compute gender distribution tallies (male vs. female) across verified evacuees. | Must | Klint M. Ruales |
| FR-ANL-06 | The system shall compute total affected population, total evacuated households, and municipal shelter capacity utilization rates. | Must | Klint M. Ruales |
| FR-ANL-07 | The system shall filter analytics by Evacuation Center and Date Range and export summary reports to PDF and CSV formats. | Must | Klint M. Ruales |

### 3.2.11 Audit Logging & Super Admin System Oversight (AUD)


| Req. ID | Verifiable Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-AUD-01 | The system shall record immutable audit log entries (operator ID, timestamp, IP address, action, target entity) for all security and data operations. | Must | Klint M. Ruales |
| FR-AUD-02 | The system shall grant Super Admins full oversight of system operations across all evacuation centers and administrative roles. | Must | Klint M. Ruales |
| FR-AUD-03 | The system shall provide Super Admins with emergency override capabilities to intervene, correct, or unlock records during field emergencies. | Must | Klint M. Ruales |
| FR-AUD-04 | The system shall log every administrative override action with mandatory justification notes to ensure accountability. | Must | Klint M. Ruales |
| FR-AUD-05 | The system shall generate consolidated, municipality-wide evacuation summary reports exportable by Super Admins. | Must | Klint M. Ruales |


## 3.3 Non-Functional Requirements

### 3.3.1 Performance

- NFR-PER-01 Response Time: Under a concurrent load of 50 field personnel across 10 centers, 95% of API requests shall complete within 1.5 seconds.
- NFR-PER-02 Scanning Latency: Optical QR code detection and household lookup shall execute and display results in under 500 milliseconds on standard smartphone hardware.
- NFR-PER-03 Search Throughput: Full-text household search over 20,000 SafeTrack citizen records shall return paginated matches within 1.0 second.
- NFR-PER-04 Analytics Aggregation: Analytics KPI computations over 100,000 historic member records shall execute in under 3.0 seconds.
- NFR-PER-05 Frontend Efficiency: The frontend production bundle shall achieve a Lighthouse Performance score >= 85 and load within 2 seconds over 4G connections.
### 3.3.2 Security & Data Confidentiality

- NFR-SEC-01 Cryptographic Storage: All operator passwords must be hashed using bcrypt with a work factor of 12; plaintext passwords shall never be persisted or logged.
- NFR-SEC-02 Parameterized Execution: All database queries must use PDO prepared statements via Eloquent ORM to eliminate SQL injection vulnerabilities.
- NFR-SEC-03 Web Application Armor: State-modifying endpoints must enforce CSRF token verification and strict Content Security Policy (CSP) headers.
- NFR-SEC-04 Demographic Isolation: SafeTrack demographic records must be isolated behind authenticated server-side controllers and never exposed via public endpoints.
- NFR-SEC-05 Brute-Force Throttling: The system shall enforce account lockout for 15 minutes after 5 consecutive failed login attempts.
- NFR-SEC-06 Server-Side Enforcement: Authorization must be evaluated on the server per request; hiding frontend buttons shall never serve as sole security enforcement.
### 3.3.3 Reliability and Disaster Availability

- NFR-REL-01 Emergency Uptime: The system shall maintain 99.5% operational availability during active disaster declaration windows.
- NFR-REL-02 Backup & Recovery: Automated database backups must execute daily with retention for 30 days; point-in-time recovery must be verified within 30 minutes.
- NFR-REL-03 Transactional Integrity: Database transactions must satisfy strict ACID properties; failed admissions shall roll back completely to prevent orphan records.
- NFR-REL-04 Operational Fallback: Field personnel must be able to switch instantaneously to manual search verification if camera hardware or QR printouts fail.
### 3.3.4 Usability & Field Accessibility

- NFR-USA-01 Admission Velocity: Evacuation personnel with basic digital literacy shall complete a household admission in under 45 seconds via QR code.
- NFR-USA-02 Visual Contrast: The UI must satisfy WCAG 2.1 Level AA contrast ratios (minimum 4.5:1 for standard text) across both dark and light modes.
- NFR-USA-03 Touch Usability: All interactive buttons and touch targets must measure at least 44x44 CSS pixels for accurate field operation on tablets.
- NFR-USA-04 Error Clarity: Validation errors and system alerts must provide descriptive, non-technical instructions guiding corrective action.
### 3.3.5 Maintainability & Clean Architecture

- NFR-MNT-01 Architecture Standards: The backend must adhere to PSR-12 coding style and Domain-Driven Design (DDD) separating Actions, Repositories, and Models.
- NFR-MNT-02 Automated Verification: Automated PHPUnit/Pest feature tests must cover 100% of Must-priority functional workflows prior to release.
- NFR-MNT-03 Migration Reproducibility: Every database mutation must be codified as version-controlled, rollback-capable Laravel migration files.
- NFR-MNT-04 Component Modularity: Frontend React components must be modular, adhering to single responsibility with typed store abstractions.
### 3.3.6 Portability

- NFR-POR-01 Environment Agnostic: The entire application must be deployable across standard cloud VPS or on-premises municipal servers without code alteration.
- NFR-POR-02 Containerization: The application must support multi-container Docker deployment (Nginx, PHP-FPM, MySQL) for seamless reproduction.
- NFR-POR-03 Browser Compatibility: The frontend client must function flawlessly across Google Chrome, Mozilla Firefox, Microsoft Edge, and Apple Safari.
### 3.3.7 Data Privacy and Regulatory Compliance

- NFR-CMP-01 RA 10173 Compliance: Full adherence to Republic Act No. 10173 (Data Privacy Act of 2012) principles of transparency, legitimate purpose, and proportionality.
- NFR-CMP-02 Purpose Limitation: SafeTrack demographic records must be utilized exclusively for evacuation verification and needs planning, never commercialized.
- NFR-CMP-03 Anonymized Reporting: Demographic analytics exports must be aggregated and anonymized to protect individual citizen identities in public reports.
- NFR-CMP-04 NDRRMC Guidelines: Adherence to NDRRMC Joint Memorandum Circular No. 1 guidelines for evacuation center registration and family tracking.
## 3.4 Database Requirements

The EvaTrack relational persistence layer is implemented in MySQL 8.0 with InnoDB storage engine and utf8mb4_unicode_ci collation. The schema encompasses 35 dedicated entities designed around Domain-Driven boundaries. The core entities and their relationships are cataloged below:

| Entity Name | Domain Scope & Description | Key Cardinalities & Relationships |
| --- | --- | --- |
| users | System operators across Super Admin, Evacuation Admin, and Evacuation Personnel roles. | N:1 roles; N:1 evacuation_centers (assigned center) |
| roles | System privilege classifications (super_admin, evac_admin, evac_personnel). | 1:N users |
| households | Master family unit ingested from SafeTrack with address and contact details. | 1:N household_members; 1:N evacuation_records; 1:N notification_recipients |
| household_members | Individual citizen records comprising birthdate, gender, and relationships. | N:1 households; N:1 genders; 1:N member_vulnerable_groups; 1:N evacuated_members |
| vulnerable_groups | Classifications of vulnerability: Senior Citizen, PWD, Pregnant, Infant, Child. | 1:N member_vulnerable_groups |
| member_vulnerable_groups | Pivot entity linking individual members to one or more vulnerability tags. | N:1 household_members; N:1 vulnerable_groups |
| evacuation_centers | Registered municipal shelter facilities with capacity and coordinates. | 1:N rooms; 1:N evacuation_records; 1:N users (personnel); 1:N resource_requests |
| rooms | Designated rooms or sub-zones within an evacuation center with individual limits. | N:1 evacuation_centers; 1:N evacuated_members |
| disaster_events | Declared municipal disaster incidents with severity and operational lifecycle. | N:1 disaster_types; N:1 severity_levels; 1:N evacuation_records |
| evacuation_records | Transactional record anchoring a household admission to a center and event. | N:1 households; N:1 evacuation_centers; N:1 disaster_events; 1:N evacuated_members |
| evacuated_members | Tracks individual member check-in timestamp, assigned room, and status. | N:1 evacuation_records; N:1 household_members; N:1 rooms; N:1 evacuation_statuses |
| resource_requests | Logistics tickets requested by center personnel and routed to ResQperation. | N:1 evacuation_centers; N:1 resource_request_statuses; N:1 urgency_levels |
| center_issue_reports | In-center facility, medical, sanitation, and security incident reports. | N:1 evacuation_centers; N:1 issue_statuses; N:1 severity_levels; N:1 categories |
| notifications | Broadcast transmission records dispatched via SMS and ResQperation push. | 1:N notification_recipients; 1:N notification_logs |
| audit_logs | Immutable forensic trace capturing operator actions, IP addresses, and before/after states. | N:1 users |

Data Integrity & Archival: Integrity Rules: Foreign key constraints enforce referential integrity with ON DELETE RESTRICT on all operational records to prevent accidental cascading destruction of active shelter data. Soft deletion (deleted_at timestamps) is utilized across users, households, centers, and records to preserve forensic trails.

# Appendix A. Preliminary Entity-Relationship Overview

The conceptual architecture of EvaTrack centers upon the evacuation_records transaction, which serves as the core operational nexus linking a SafeTrack household to an evacuation_center under an active disaster_event. The individual evacuated_members records capture fine-grained presence, room allocation, and decampment status. Supporting operational subsystems branch outward from evacuation_centers: rooms govern spatial allocations; resource_requests track logistics demands routed to ResQperation; center_issue_reports capture facility health; and notifications record multi-channel broadcasts. The full 35-entity diagram is cataloged in the software design artifact evatrack_erd_reference.md.
# Appendix B. Requirements Traceability Matrix

The following matrix maps every explicit Must-Have requirement from EVATRACK-Must-Haves (1).docx to its corresponding Functional Requirement ID, implemented backend/frontend modules, and designated student proponent:

| Baseline Must-Have Requirement | FR Mapping | Implementing Module / Action | Proponent |
| --- | --- | --- | --- |
| Retrieve SafeTrack demographic data for identification & verification only; data hidden from households. | FR-STK-01<br>FR-STK-03<br>FR-STK-04 | SafeTrackSyncService<br>HouseholdController | Vhenz Cernal |
| Track household evacuation status as Evacuated or Not Evacuated. | FR-VER-04<br>FR-STAY-03 | UpdateEvacuatedMemberStatusAction<br>HouseholdRepository | Vhenz Cernal |
| Automatically update evacuation center capacity when household is verified. | FR-CTR-03<br>FR-VER-04 | AdmitHouseholdAction<br>CenterCapacityService | Vhenz Cernal |
| Compute evacuation analytics from demographics (age, pregnant, PWD, gender, affected pop). | FR-ANL-01<br>FR-ANL-02<br>FR-ANL-06 | LiveAnalyticsService<br>AnalyticsKPIs.jsx | Klint M. Ruales |
| Route alerts and assistance requests to households and ResQperation. | FR-RES-03<br>FR-NOTIF-02<br>FR-NOTIF-03 | ResQperationDispatchService<br>SMSGatewayService | Anna Rhea Villadolid |
| Evacuation Personnel: Log in (evacuation personnel role). | FR-AUTH-01<br>FR-AUTH-02 | LoginAction<br>RoleMiddleware | Anna Rhea Villadolid |
| Verify evacuated households via QR code scanning or manual input. | FR-VER-01<br>FR-VER-02<br>FR-VER-03 | SearchHouseholdsAction<br>RegistrySearch.jsx | Klint M. Ruales |
| Update household info post-verification: assign rooms, monitor stay, update status. | FR-STAY-01<br>FR-STAY-02<br>FR-STAY-03 | AssignRoomAction<br>EvacuationDetail.jsx | Danica Gelbolingo |
| Report in-center needs (shortages of resources and extra personnel requests). | FR-ISS-01<br>FR-RES-01<br>FR-RES-02 | CreateCenterIssueReportAction<br>CreateResourceRequestAction | Anna Rhea Villadolid |
| Request resources and personnel assistance from ResQperation. | FR-RES-02<br>FR-RES-03 | ResQperationRequestService<br>ResourceRequests.jsx | Anna Rhea Villadolid |
| Evacuation Admin: Log in (evacuation admin role). | FR-AUTH-01<br>FR-AUTH-02 | LoginAction<br>RoleMiddleware | Anna Rhea Villadolid |
| Create and configure evacuation centers. | FR-CTR-01<br>FR-CTR-04 | CreateCenterAction<br>EvacuationList.jsx | Vhenz Cernal |
| Create and configure rooms within an evacuation center. | FR-CTR-02 | CreateRoomAction<br>RoomManagementModal.jsx | Danica Gelbolingo |
| Monitor ongoing evacuations across centers (status, occupancy, personnel activity). | FR-CTR-04<br>FR-EVT-02 | LiveDashboardService<br>Dashboard.jsx | Klint M. Ruales |
| Monitor ongoing requests per evacuation centers. | FR-RES-04<br>FR-RES-06 | ListResourceRequestsAction<br>ResourceRequestsAnalytics.jsx | Anna Rhea Villadolid |
| Send evacuation alerts (SMS for non-internet; push via ResQperation for app). | FR-NOTIF-01<br>FR-NOTIF-02<br>FR-NOTIF-03 | DispatchNotificationAction<br>EvacuationAlerts.jsx | Klint M. Ruales |
| Super Admin: System-wide reports, full oversight, and emergency override access. | FR-AUD-02<br>FR-AUD-03<br>FR-AUD-05 | AnalyticsExportController<br>SuperAdminOverrideMiddleware | Klint M. Ruales |


# Appendix C. Capstone Approval & Sign-Off Sheet

By signing below, the capstone adviser, project coordinator, panel evaluators, and student proponents attest that this Software Requirements Specification has been thoroughly evaluated and approved as the definitive technical and functional baseline for EVATRACK (Version 1.0).

| Role in Capstone Project | Full Name & Academic Title | Signature / Date |
| --- | --- | --- |
| Student Proponent (Lead) | Klint M. Ruales | _________________________ |
| Student Proponent | Danica Gelbolingo | _________________________ |
| Student Proponent | Anna Rhea Villadolid | _________________________ |
| Student Proponent | Vhenz Cernal | _________________________ |
| Capstone Project Adviser | Mr. Stefan A.D. Flores | _________________________ |
| Capstone Project Coordinator | Mr. Roderick A. Bandalan | _________________________ |
| Panel Evaluation Chair | Mr. Eric Magto | _________________________ |
| Panel Evaluator | Ms. Khiara Danielle Rubia | _________________________ |

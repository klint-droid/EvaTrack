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
| 0.1 | 2026-07-20 | Initial draft covering project scope, core system requirements, and stakeholder roles. | All proponents |
| 0.5 | 2026-08-20 | Defined functional and non-functional requirements and integration workflows with SafeTrack and ResQperation. | Ruales, Gelbolingo, Villadolid, Cernal |
| 1.0 | 2026-10-31 | Finalized SRS aligned with the actual implemented EvaTrack system, verified backend domain actions, frontend components, and active database schema. | All proponents |

# Table of Contents

Revision History ........................................... 2
Table of Contents ........................................... 3
1. Introduction ........................................... 4
1.1 Purpose ........................................... 4
1.2 Scope ........................................... 4
1.3 Definitions, Acronyms, and Abbreviations ........................................... 5
1.4 References ........................................... 6
1.5 Overview ........................................... 6
2. Overall Description ........................................... 7
2.1 Product Perspective ........................................... 7
2.2 Product Functions ........................................... 8
2.3 User Classes and Characteristics ........................................... 8
2.4 Operating Environment ........................................... 9
2.5 Design and Implementation Constraints ........................................... 9
2.6 Assumptions and Dependencies ........................................... 10
2.7 Apportioning of Requirements ........................................... 10
3. Specific Requirements ........................................... 11
3.1 External Interface Requirements ........................................... 11
3.1.1 User Interfaces ........................................... 11
3.1.2 Hardware Interfaces ........................................... 11
3.1.3 Software Interfaces ........................................... 12
3.1.4 Communications Interfaces ........................................... 12
3.2 Functional Requirements ........................................... 13
3.2.1 Authentication & User Account Management (AUTH) ........................................... 13
3.2.2 SafeTrack Demographics & Privacy Isolation (STK) ........................................... 14
3.2.3 Evacuation Center & Accommodation Unit Management (CTR) ........................................... 15
3.2.4 Household Verification & Check-In Admission (VER) ........................................... 16
3.2.5 In-Center Stay, Unit Allocation & Checkout (STAY) ........................................... 17
3.2.6 Disaster Event Management (EVT) ........................................... 18
3.2.7 Center Issue & Facility Condition Reporting (ISS) ........................................... 19
3.2.8 Resource Requests & ResQperation Coordination (RES) ........................................... 20
3.2.9 Emergency Alerts: SMS & Push Notifications (NOTIF) ........................................... 21
3.2.10 Evacuation Analytics & Report Exports (ANL) ........................................... 22
3.2.11 Super Admin System Oversight & In-App Alerts (OVR) ........................................... 23
3.3 Non-Functional Requirements ........................................... 24
3.3.1 Performance ........................................... 24
3.3.2 Security & Data Confidentiality ........................................... 24
3.3.3 Reliability and Availability ........................................... 25
3.3.4 Usability & Accessibility ........................................... 25
3.3.5 Maintainability & Architecture ........................................... 26
3.3.6 Portability ........................................... 26
3.3.7 Data Privacy Compliance ........................................... 26
3.4 Database Requirements ........................................... 27
Appendix A. Entity-Relationship Overview ........................................... 30
Appendix B. Requirements Traceability Matrix ........................................... 31
Appendix C. Capstone Approval & Sign-Off Sheet ........................................... 34

# 1. Introduction

## 1.1 Purpose

Purpose and Objective: This Software Requirements Specification (SRS) defines the functional and non-functional requirements for EVATRACK (Version 1.0) — a web-based evacuation management and coordination platform. This document serves as the official technical reference for the student proponents, the capstone adviser, the project coordinator, and the evaluation panel of the School of Computer Studies at the University of San Jose-Recoletos (USJ-R). Every requirement described in this document directly reflects the features, workflows, and database structure implemented in the EvaTrack system.
## 1.2 Scope

System Scope: EvaTrack is a web-based platform designed to help Local Government Units (LGUs) and barangay disaster response teams manage evacuation centers efficiently during disasters. The system handles the complete evacuation workflow: setting up evacuation centers and accommodation units, verifying and admitting arriving households via QR code scanning or manual search, assigning families to shelter units, monitoring shelter occupancy in real time, reporting facility issues, requesting supplies, broadcasting emergency alerts, and generating demographic analytics.
EvaTrack connects with two partner systems in the disaster management workflow:
(1) SafeTrack Demographics System: EvaTrack retrieves registered household profiles, family member lists, addresses, and vulnerability indicators from SafeTrack. To protect resident privacy, this demographic information is hidden from the public and is accessible only to authorized system operators for verifying evacuees and computing shelter analytics.
(2) ResQperation Response Platform: EvaTrack forwards resource and personnel assistance requests from evacuation centers to ResQperation and uses push notification services to send emergency evacuation alerts to residents with the mobile app.
User Roles & Access: The system enforces Role-Based Access Control (RBAC) across three operator roles: Super Admin, Evacuation Admin, and Evacuation Personnel. Residents do not need user accounts; they can view public shelter availability on the EvaTrack Public Portal map, receive SMS and push alerts, and present their QR code upon arriving at an evacuation center.
## 1.3 Definitions, Acronyms, and Abbreviations


| Term / Acronym | Definition |
| --- | --- |
| EvaTrack | The web-based evacuation center management and coordination platform described in this document. |
| SafeTrack | The partner demographic profiling system that provides registered household and family member records. |
| ResQperation | The partner disaster response platform that receives resource requests and delivers mobile push alerts. |
| Accommodation Unit | A designated living space inside an evacuation center (Family Room, Dormitory, Tent, Classroom, Gymnasium, or Covered Court) with a specific maximum capacity. |
| RBAC | Role-Based Access Control — restricts system features and center data based on the logged-in user's role. |
| QR Code | Quick Response code containing a household's unique identifier used for fast check-in verification at an evacuation center. |
| SMS | Short Message Service — text messages sent via the TextBee SMS gateway to notify residents who do not have internet access. |
| Push Notification | Mobile app alerts sent via OneSignal / ResQperation to notify residents who have internet connectivity. |
| PWD | Person with Disability — a vulnerable group tracked by the system to help prioritize shelter care and analytics. |
| RA 10173 | Republic Act No. 10173 (Data Privacy Act of 2012, Philippines), governing the protection of personal household data. |
| REST API | Representational State Transfer Application Programming Interface used for communication between the React frontend, Laravel backend, and partner systems. |
| DTO | Data Transfer Object — a structured PHP object used in the backend to pass validated request data cleanly into domain actions. |

## 1.4 References

- ISO/IEC/IEEE 29148:2018: Systems and software engineering — Life cycle processes — Requirements engineering.
- Republic Act No. 10173: Data Privacy Act of 2012, National Privacy Commission, Republic of the Philippines.
- Joint Memorandum Circular No. 1 (2013): Guidelines on Evacuation Center Coordination and Management, NDRRMC / DSWD / DILG / DOH.
- Laravel Framework Reference: Laravel 11.x/12.x Official Documentation, https://laravel.com/docs.
- React Frontend Reference: React 18.x & TanStack React Query Documentation, https://react.dev.
- MySQL 8.0 Database Reference: MySQL 8.0 Reference Manual, Oracle Corporation, https://dev.mysql.com/doc/.
- EVATRACK-Must-Haves (1).docx: EvaTrack Capstone Project Baseline Requirements, School of Computer Studies, USJ-R.

## 1.5 Overview

Section 2 provides an overall description of the EvaTrack platform, including its architecture, core features, user roles, operating environment, and constraints. Section 3 details the specific requirements, covering user interfaces, software integrations, functional requirements for all 11 system modules, non-functional requirements, and the active MySQL database tables. Appendices A, B, and C present the Entity-Relationship overview, the Requirements Traceability Matrix mapped to the actual codebase, and the Capstone Approval Sheet.
# 2. Overall Description

## 2.1 Product Perspective

System Architecture: EvaTrack is built as a modern web application with a clear separation between the frontend user interface, the backend API, and the relational database:
(a) Frontend Application (EvaTrackFrontEnd): Built with React 18, Vite, and Tailwind CSS. It uses TanStack React Query for client-side data caching and instant page transitions, Zustand for reactive user and permission state, Leaflet for interactive evacuation center maps, Recharts for demographic charts, and an HTML5 camera scanner for reading household QR codes.
(b) Backend API (EvaTrack): Built with Laravel and organized into modular domains (Authentication, Households, EvacuationCenters, AccommodationUnits, Evacuations, EvacuationEvents, CenterIssueReports, ResourceRequests, Notifications, Analytics, and ReferenceData). Each feature is handled by dedicated Controller, Action, Form Request, DTO, and Eloquent Repository classes.
(c) Database Layer (mysql_v2 / shared_db): A MySQL 8.0 relational database containing the 31 active tables used by the system, enforcing foreign key relationships and soft deletes.
System Integrations: EvaTrack connects to SafeTrack data to verify households and members without exposing private records on public pages. It also connects with ResQperation to forward resource requests and send mobile push alerts, alongside TextBee for sending SMS alerts to residents without internet access.
## 2.2 Product Functions

EvaTrack provides the following core functions:
- SafeTrack Demographic Access: Retrieves household profiles, family members, addresses, and vulnerability groups from SafeTrack strictly for authorized personnel verification.
- Household Verification & Check-In: Verifies arriving households through camera-based QR code scanning or manual search by household name, member name, or contact number.
- Evacuation Status Tracking: Tracks the status of households (Not Evacuated, Evacuated, Checked Out) and records which individual family members are present in the center.
- Automatic Capacity & Occupancy Updates: Automatically updates evacuation center occupancy and accommodation unit slot counts whenever households are admitted, assigned to units, or checked out.
- Center & Accommodation Unit Management: Allows operators to manage evacuation centers and configure accommodation units (Family Room, Dormitory, Tent, Classroom, Gymnasium, Covered Court).
- Resource Request Routing: Enables personnel to submit resource and personnel requests and routes them to Evacuation Admins and ResQperation.
- Center Issue Reporting: Allows personnel to report in-center facility, medical, sanitation, or security issues with severity levels and photo attachments, and lets admins update their resolution status.
- Multi-Channel Emergency Alerts: Sends emergency evacuation alerts via SMS (TextBee) for offline residents and Push Notifications (OneSignal / ResQperation) for mobile app users.
- Demographic & Vulnerability Analytics: Computes live evacuation statistics from verified evacuees, including age groups (infants, children, teens, adults, seniors), pregnant women, PWDs, gender counts, and shelter occupancy rates, with CSV and PDF export.
- Public Evacuation Map Portal: Provides a public map portal where citizens can view active evacuation centers and available capacity without logging in.

## 2.3 User Classes and Characteristics


| User Class | System Responsibilities & Scope | Access Level |
| --- | --- | --- |
| Super Admin | Has full oversight across all evacuation centers, disaster events, households, and reports. Exclusively authorized to register new user accounts (POST /api/users). Can manage centers, units, personnel assignments, and perform emergency overrides across all system modules. | Full system-wide administrative access. |
| Evacuation Admin | Creates and updates evacuation centers, configures accommodation units, and assigns Evacuation Personnel to centers. Declares and closes disaster events, monitors shelter occupancy, updates center issue statuses, reviews resource requests, and sends SMS and push alerts. | Administrative access across evacuation centers and operations. |
| Evacuation Personnel | Assigned to a specific evacuation center. Scans household QR codes or searches records to verify and admit evacuees, creates and manages accommodation units within their assigned center, assigns households to units, updates member presence, processes household checkouts, reports center issues, and submits resource requests. | Operational access scoped to their assigned evacuation center. |
| Households / Residents<br>(Passive Actor) | View active evacuation centers and available slots on the Public Portal map, receive SMS and push evacuation alerts, and present their QR code or household details to personnel upon arrival at a shelter. | Public portal access only; no login account required. |
| SafeTrack System<br>(External System) | Provides pre-registered household records, family member details, addresses, and vulnerability classifications for verification and analytics. | Authenticated API and shared database integration. |
| ResQperation System<br>(External System) | Receives resource and personnel assistance requests from EvaTrack and delivers push notification alerts to residents. | Authenticated REST API integration. |

## 2.4 Operating Environment

- Backend Server Environment: PHP 8.2+, Laravel 11/12 framework, Composer 2.x, and MySQL 8.0 database server.
- Client Browser Environment: Modern web browsers (Google Chrome, Microsoft Edge, Mozilla Firefox, Apple Safari) on desktop computers, laptops, tablets, and smartphones.
- Hardware Peripherals: Device camera (smartphone, tablet, or webcam) supporting browser media permissions for QR code scanning.
- Network & Gateways: HTTPS network connection for API communication, TextBee SMS gateway dispatch, and OneSignal / ResQperation push notification delivery.

## 2.5 Design and Implementation Constraints

- Backend Architecture: The backend uses Laravel organized into domain folders with Controllers, Actions, Form Requests, DTOs, Models, and Eloquent Repositories.
- Frontend Architecture: The frontend uses React 18, Vite, Tailwind CSS, TanStack React Query for caching, and Zustand for role and capability state management.
- Database Security: All database queries use Laravel Eloquent ORM or parameterized queries to prevent SQL injection.
- Data Privacy Protection: In compliance with RA 10173 (Data Privacy Act of 2012), SafeTrack household and member records are protected behind authenticated operator routes and are never shown on the public portal.

## 2.6 Assumptions and Dependencies

- Operational Setup: Local Government Units register official evacuation centers and assign personnel accounts before or during disaster operations.
- SafeTrack Data Availability: Household and family member demographic records from SafeTrack are available in the database for verification.
- External Service Connectivity: ResQperation API endpoints, OneSignal push services, and the TextBee SMS gateway are reachable when dispatching external requests and alerts.

## 2.7 Apportioning of Requirements

Requirements marked as 'Must' in Section 3 represent the essential features required by the EVATRACK Must-Haves specification and implemented in the system. Requirements marked as 'Should' represent supporting usability and reporting features that improve day-to-day operations.
# 3. Specific Requirements

## 3.1 External Interface Requirements

### 3.1.1 User Interfaces

- UI-01 Responsive Layout: Responsive web interface built with React and Tailwind CSS that adapts cleanly across mobile phones, tablets, and desktop screens, including a mobile bottom navigation bar and desktop sidebar.
- UI-02 Light & Dark Theme: Supports switching between Light Mode and Dark Mode, saving the user's preference in browser storage.
- UI-03 Command Dashboard: Displays active shelter capacities, available slots, occupancy ratios, active issue counts, and pending resource logistics on the operator Dashboard.
- UI-04 Standardized Data Tables: Provides searchable, filterable, and paginated tables across Evacuation Centers, Accommodation Units, Households, Issue Reports, Resource Requests, Alerts, and User Management.
- UI-05 QR Scanner & Verification View: Provides an interactive camera modal in the Verify Household module for scanning household QR codes, alongside an instant manual search bar.
- UI-06 Public Evacuation Map Portal: Provides a Public Portal with an interactive Leaflet map showing evacuation center locations, capacity status, and routing guidance for citizens.

### 3.1.2 Hardware Interfaces

- HW-01 Camera Device Interface: Uses smartphone, tablet, or computer cameras through the browser MediaDevices API to scan household QR codes.
- HW-02 Export & Print Interface: Uses the browser print and file download interface to export PDF and CSV reports for centers and analytics.

### 3.1.3 Software Interfaces


| Interface Name | Purpose in EvaTrack | Integration Method |
| --- | --- | --- |
| SafeTrack Demographics Integration | Provides household profiles, family member rosters, addresses, and vulnerability groups (Senior Citizen, PWD, Pregnant, Infant, Child) for verification. | REST API (ExternalHouseholdService) and shared MySQL database tables. |
| ResQperation Resource & Push Integration | Receives resource and personnel requests from evacuation centers and supports push notification alerts to residents. | REST API JSON requests and OneSignalService push notification dispatch. |
| TextBee SMS Gateway | Sends SMS evacuation alerts to registered household phone numbers for residents without internet access. | HTTPS REST API via TextBeeService. |
| Leaflet & OpenStreetMap | Renders interactive maps for selecting evacuation center coordinates and displaying shelter locations on the Public Portal. | React-Leaflet map components and reverse geocoding utility. |

### 3.1.4 Communications Interfaces

- COM-01 JSON REST Communication: All API requests between the React frontend and Laravel backend use JSON over HTTP/HTTPS.
- COM-02 Token Authentication: Protected API routes use Laravel Sanctum bearer tokens and session authentication, automatically clearing sessions on 401 Unauthorized responses.
- COM-03 Request Security: All state-changing requests include CSRF protection headers (X-XSRF-TOKEN) and server-side validation.

## 3.2 Functional Requirements

The functional requirements below are organized by system module. Every requirement corresponds directly to implemented features in EvaTrack and the baseline requirements in EVATRACK-Must-Haves (1).docx.
### 3.2.1 Authentication & User Account Management (AUTH)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-AUTH-01 | The system shall authenticate users (Super Admin, Evacuation Admin, Evacuation Personnel) using their registered email/credentials and hashed password, supporting OTP verification. | Must | Anna Rhea Villadolid |
| FR-AUTH-02 | The system shall enforce Role-Based Access Control (RBAC) on both backend API routes and frontend views based on the user's assigned role. | Must | Anna Rhea Villadolid |
| FR-AUTH-03 | The system shall restrict Evacuation Personnel to managing operations only within their assigned evacuation center (assigned_center_id). | Must | Anna Rhea Villadolid |
| FR-AUTH-04 | The system shall allow only the Super Admin to register new user accounts (POST /api/users), while allowing Super Admins and Evacuation Admins to update users and assign personnel to centers. | Must | Anna Rhea Villadolid |
| FR-AUTH-05 | The system shall revoke the user's authentication token immediately upon logout. | Must | Anna Rhea Villadolid |
| FR-AUTH-06 | The system shall allow logged-in users to update their profile details, profile photo, and account password. | Should | Anna Rhea Villadolid |

### 3.2.2 SafeTrack Demographics & Privacy Isolation (STK)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-STK-01 | The system shall retrieve household records from SafeTrack, including the household name, contact number, address hierarchy (region, province, city, barangay, sitio, purok), and family member list. | Must | Vhenz Cernal |
| FR-STK-02 | The system shall retrieve vulnerability classifications for household members (Senior Citizen, PWD, Pregnant, Infant, Child). | Must | Vhenz Cernal |
| FR-STK-03 | The system shall restrict access to SafeTrack household and member demographic records strictly to authenticated system operators for verification and analytics. | Must | Vhenz Cernal |
| FR-STK-04 | The system shall hide personal household demographic data from the public portal so that residents and unauthenticated visitors cannot view private citizen information. | Must | Vhenz Cernal |
| FR-STK-05 | The system shall allow authorized operators to manage household profiles and family member records when updating registry details. | Should | Vhenz Cernal |

### 3.2.3 Evacuation Center & Accommodation Unit Management (CTR)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-CTR-01 | The system shall allow Super Admins and Evacuation Admins to create, update, and delete evacuation centers with a center name, address, map coordinates (latitude/longitude), contact number, and maximum capacity. | Must | Vhenz Cernal |
| FR-CTR-02 | The system shall allow Super Admins, Evacuation Admins, and assigned Evacuation Personnel to create, update, and delete accommodation units within an evacuation center, selecting a unit type (Family Room, Dormitory, Tent, Classroom, Gymnasium, Covered Court) and unit max capacity. | Must | Danica Gelbolingo |
| FR-CTR-03 | The system shall validate that the total capacity of accommodation units inside a center does not exceed the evacuation center's maximum capacity. | Must | Danica Gelbolingo |
| FR-CTR-04 | The system shall automatically update the center's current occupancy and available slots whenever households are admitted, updated, or checked out. | Must | Vhenz Cernal |
| FR-CTR-05 | The system shall display real-time occupancy progress bars and status indicators (Available, Near Full, Full) for each evacuation center and accommodation unit. | Must | Vhenz Cernal |
| FR-CTR-06 | The system shall allow Super Admins and Evacuation Admins to assign Evacuation Personnel to a specific evacuation center. | Must | Danica Gelbolingo |

### 3.2.4 Household Verification & Check-In Admission (VER)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-VER-01 | The system shall allow Evacuation Personnel to verify arriving households by scanning their QR code using the device camera. | Must | Klint M. Ruales |
| FR-VER-02 | The system shall provide a manual search option to find and verify households by household name, member name, household ID, or contact number. | Must | Klint M. Ruales |
| FR-VER-03 | The system shall display the household's family member list during verification so personnel can check in the specific members who are physically present. | Must | Klint M. Ruales |
| FR-VER-04 | The system shall create an evacuation record and update the household's status to 'Evacuated' (household_status_id = 2) once members are checked in. | Must | Vhenz Cernal |
| FR-VER-05 | The system shall disable the check-in action and show an 'Admitted' status once all members of a household have been verified and admitted. | Must | Klint M. Ruales |
| FR-VER-06 | The system shall record the verifying personnel's user ID, the active disaster event ID, the admission timestamp, and the verification method (QR or manual). | Must | Klint M. Ruales |
| FR-VER-07 | The system shall prevent duplicate active admissions of the same household across different evacuation centers during the same disaster event. | Must | Klint M. Ruales |

### 3.2.5 In-Center Stay, Unit Allocation & Checkout (STAY)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-STAY-01 | The system shall allow Evacuation Personnel and Admins to assign admitted households to an available accommodation unit (unit_allocations) within the evacuation center. | Must | Danica Gelbolingo |
| FR-STAY-02 | The system shall allow personnel to unassign or transfer a household from an accommodation unit when needed. | Must | Danica Gelbolingo |
| FR-STAY-03 | The system shall track and display the household's check-in date and time and monitor their stay inside the evacuation center. | Must | Danica Gelbolingo |
| FR-STAY-04 | The system shall allow personnel to update individual family member evacuation status (Evacuated vs. Not Evacuated) during their stay, automatically adjusting the center's evacuated count. | Must | Danica Gelbolingo |
| FR-STAY-05 | The system shall allow personnel to process household checkout, setting the household status to 'Checked Out' (household_status_id = 6) and recording the checkout timestamp. | Must | Danica Gelbolingo |
| FR-STAY-06 | The system shall automatically release the household's assigned accommodation unit slots and deduct their member count from the center's active occupancy upon checkout. | Must | Vhenz Cernal |

### 3.2.6 Disaster Event Management (EVT)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-EVT-01 | The system shall allow Super Admins and Evacuation Admins to declare a disaster event by specifying the event name, disaster type (e.g., Typhoon, Flood, Earthquake, Fire), and geographic scope. | Must | Vhenz Cernal |
| FR-EVT-02 | The system shall allow admins to assign operational evacuation centers to an active disaster event. | Must | Vhenz Cernal |
| FR-EVT-03 | The system shall display the count of active emergencies and closed historical incidents on the Disaster Events management page. | Must | Klint M. Ruales |
| FR-EVT-04 | The system shall allow Super Admins and Evacuation Admins to end an active disaster event, recording the end timestamp and moving it to the historical events log. | Must | Vhenz Cernal |
| FR-EVT-05 | The system shall allow operators to filter evacuation records, issue reports, resource requests, alerts, and analytics by disaster event. | Should | Klint M. Ruales |

### 3.2.7 Center Issue & Facility Condition Reporting (ISS)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-ISS-01 | The system shall allow Evacuation Personnel and Admins to submit center issue reports categorized by issue category (e.g., Facility Damage, Sanitation, Medical/Health, Security). | Must | Anna Rhea Villadolid |
| FR-ISS-02 | The system shall record the issue title, description, severity level (low, medium, high, critical), reporting user, and assigned evacuation center. | Must | Anna Rhea Villadolid |
| FR-ISS-03 | The system shall allow Super Admins and Evacuation Admins to update the status of center issue reports (open, in_progress, resolved, closed). | Must | Anna Rhea Villadolid |
| FR-ISS-04 | The system shall allow Evacuation Personnel to edit or delete their own reported issues while the issue status is still 'open'. | Should | Anna Rhea Villadolid |
| FR-ISS-05 | The system shall display active center issue counts and severity breakdowns on the Dashboard and Analytics pages. | Must | Klint M. Ruales |

### 3.2.8 Resource Requests & ResQperation Coordination (RES)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-RES-01 | The system shall allow Evacuation Personnel and Admins to submit resource requests specifying the request type (resource or personnel), item/resource name, quantity needed, description, and evacuation center. | Must | Anna Rhea Villadolid |
| FR-RES-02 | The system shall route resource and personnel assistance requests to ResQperation (target_agency = 'ResQperation') for external response support. | Must | Anna Rhea Villadolid |
| FR-RES-03 | The system shall track the status of each resource request (pending, approved, fulfilled, rejected) and display them in a searchable, filterable table. | Must | Anna Rhea Villadolid |
| FR-RES-04 | The system shall allow external response updates or authorized administrators to update resource request fulfillment statuses. | Must | Anna Rhea Villadolid |
| FR-RES-05 | The system shall summarize pending and fulfilled resource requests per center on the Dashboard and Analytics modules. | Must | Klint M. Ruales |

### 3.2.9 Emergency Alerts: SMS & Push Notifications (NOTIF)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-NOTIF-01 | The system shall provide an Evacuation Alerts interface where Super Admins and Evacuation Admins exclusively can create and broadcast emergency alerts (access is strictly restricted from Evacuation Personnel to centralize disaster directives). | Must | Klint M. Ruales |
| FR-NOTIF-02 | The system shall send SMS alerts via the TextBee SMS gateway (TextBeeService) to registered household contact numbers for residents without internet access. | Must | Klint M. Ruales |
| FR-NOTIF-03 | The system shall send mobile push notifications via OneSignal / ResQperation (OneSignalService) to residents with registered device tokens. | Must | Klint M. Ruales |
| FR-NOTIF-04 | The system shall support targeting alerts by evacuation center, disaster event, or audience filter (`not_evacuated` for pre-disaster calls, `all` for public broadcasts), and support instant, scheduled, or recurring dispatches. | Must | Klint M. Ruales |
| FR-NOTIF-05 | The system shall log each broadcast in notifications, notification_recipients, and notification_logs, tracking delivery status (sent, failed, pending). | Must | Klint M. Ruales |
| FR-NOTIF-06 | The system shall provide an automated advisory generator allowing administrators to draft an 'EVATRACK ADVISORY' summarizing real-time shelter occupancy, identifying near-capacity shelters (≥80% full) to avoid, and recommending shelters with open capacity for crowd redirection. | Must | Klint M. Ruales |

### 3.2.10 Evacuation Analytics & Report Exports (ANL)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-ANL-01 | The system shall compute real-time evacuation analytics from verified evacuation records and evacuated household members via LiveAnalyticsService. | Must | Klint M. Ruales |
| FR-ANL-02 | The system shall calculate age group breakdowns of evacuated members (Infants 0–2, Children 3–12, Teens 13–17, Adults 18–59, Seniors 60+). | Must | Klint M. Ruales |
| FR-ANL-03 | The system shall calculate the total number of pregnant women admitted across evacuation centers. | Must | Klint M. Ruales |
| FR-ANL-04 | The system shall calculate the total number of Persons with Disabilities (PWDs) and Senior Citizens admitted. | Must | Klint M. Ruales |
| FR-ANL-05 | The system shall calculate gender distribution counts (Male vs. Female) among verified evacuees. | Must | Klint M. Ruales |
| FR-ANL-06 | The system shall calculate total evacuated households, total evacuated individuals, and center capacity utilization rates. | Must | Klint M. Ruales |
| FR-ANL-07 | The system shall allow filtering analytics by Evacuation Center, Disaster Event, and Date Range, and support exporting reports to PDF and CSV. | Must | Klint M. Ruales |

### 3.2.11 Super Admin System Oversight & In-App Alerts (OVR)


| Req. ID | Requirement Description | Priority | Attribution |
| --- | --- | --- | --- |
| FR-OVR-01 | The system shall grant the Super Admin full visibility and management access across all evacuation centers, disaster events, households, issue reports, and resource requests. | Must | Klint M. Ruales |
| FR-OVR-02 | The system shall allow the Super Admin to override center-level restrictions to edit, admit, checkout, or clean up records across any evacuation center during emergencies. | Must | Klint M. Ruales |
| FR-OVR-03 | The system shall generate in-app user notifications (user_notifications) to alert operators about new issue reports, resource request updates, and system events. | Must | Klint M. Ruales |
| FR-OVR-04 | The system shall allow Super Admins and Evacuation Admins to export consolidated center and municipality-wide evacuation reports. | Must | Klint M. Ruales |

## 3.3 Non-Functional Requirements

### 3.3.1 Performance

- NFR-PER-01 API Response Time: Standard API requests for center details, household lists, and analytics shall respond within 1.5 seconds under normal operational load.
- NFR-PER-02 Client-Side Caching: The frontend shall cache API responses using TanStack React Query so that revisiting previously loaded pages (Dashboard, Centers, Households, Analytics) renders data immediately without redundant loading spinners.
- NFR-PER-03 QR Verification Speed: QR code scanning and household lookup shall resolve and display the family member roster within 1 second.

### 3.3.2 Security & Data Confidentiality

- NFR-SEC-01 Password Hashing: All user passwords must be hashed using bcrypt before being stored in the users table.
- NFR-SEC-02 SQL Injection Prevention: All database queries must use Eloquent ORM or prepared statements to prevent SQL injection.
- NFR-SEC-03 Demographic Data Isolation: SafeTrack household and member records must only be accessible through authenticated API endpoints and must never be exposed on public routes.
- NFR-SEC-04 Role Verification: Role permissions must be verified on both the backend controller/middleware layer and the frontend capability matrix.

### 3.3.3 Reliability and Availability

- NFR-REL-01 Data Consistency: Database operations that modify multiple tables (such as admitting a household, recording evacuated members, and updating center occupancy) must run safely so partial failures do not corrupt capacity counts.
- NFR-REL-02 Manual Verification Fallback: If a household's QR code is damaged or a camera is unavailable, personnel can immediately use the manual search bar to verify and admit the household.
- NFR-REL-03 Soft Deletion Recovery: Core tables (users, households, household_members, evacuation_centers, accommodation_units, disaster_events) use soft deletes (deleted_at) so historical records are not permanently lost by accident.

### 3.3.4 Usability & Accessibility

- NFR-USA-01 Clear Operator Workflow: The user interface provides consistent tables, status badges, confirmation modals, and clear error messages so field personnel can operate the system with minimal training.
- NFR-USA-02 Visual Readability: Supports both Light Mode and Dark Mode with readable text contrast for indoor or night-time shelter operations.

### 3.3.5 Maintainability & Architecture

- NFR-MNT-01 Modular Backend Domains: The Laravel backend follows a Domain-Driven folder structure where each module has its own Controllers, Actions, DTOs, Requests, Models, and Repositories.
- NFR-MNT-02 Clean Frontend Architecture: The React frontend separates business logic into custom hooks (e.g., useEvacuationDetail, useCenterUnits, useCenterHouseholds, useVerifyHousehold) and reusable UI components (Button, Card, Table, Modal).

### 3.3.6 Portability

- NFR-POR-01 Cross-Device Web Access: The web application runs in any modern desktop or mobile web browser without requiring users to install a native app.
- NFR-POR-02 Reproducible Setup: Database schema definitions and seeders are maintained in version control for consistent setup across development and production servers.

### 3.3.7 Data Privacy Compliance

- NFR-CMP-01 RA 10173 Compliance: Complies with Republic Act No. 10173 (Data Privacy Act of 2012) by limiting household demographic access strictly to authorized disaster response personnel.
- NFR-CMP-02 Public Privacy Protection: Publicly accessible pages (Landing and Public Portal) display only aggregated shelter capacities and facility locations, keeping all personal citizen data private.

## 3.4 Database Requirements

The EvaTrack database (shared_db / mysql_v2) is implemented in MySQL 8.0 using the InnoDB engine. The system actively uses the following core database tables organized by functional domain:

| Table Name | System Purpose & Description | Key Relationships |
| --- | --- | --- |
| users | Stores system operator accounts (Super Admin, Evacuation Admin, Evacuation Personnel) and their assigned center. | Belongs to roles and evacuation_centers; has many user_notifications |
| roles | Defines the 3 system roles: super_admin (1), evac_admin (2), and evac_personnel (3). | Has many users |
| user_notifications | Stores in-app notification alerts for logged-in system operators. | Belongs to users |
| households | Master household records from SafeTrack, including household head name, contact number, and status. | Belongs to addresses; has many household_members and evacuation_records |
| household_members | Individual family member records including full name, birthdate, gender, civil status, and relationship. | Belongs to households, genders, civil_statuses, relationships; has many member_vulnerable_groups |
| household_statuses | Lookup statuses for households and members (1 = Not Evacuated, 2 = Evacuated, 6 = Checked Out). | Referenced by evacuation_records and evacuated_members |
| vulnerable_groups & member_vulnerable_groups | Defines vulnerability categories (Senior Citizen, PWD, Pregnant, Infant, Child) and links them to members. | Many-to-many between household_members and vulnerable_groups |
| addresses & location tables | Stores household addresses linked to regions, provinces, cities, barangays, sitios, puroks, and zipcodes. | Referenced by households |
| disaster_events, disaster_types & severity_levels | Stores declared disaster events, their disaster type (Typhoon, Flood, Fire, etc.), severity level, and start/end dates. | Has many evacuation_records, notifications, and analytics |
| evacuation_centers & center_occupancies | Stores registered evacuation shelters, GPS coordinates, max capacity, current event link, and live occupancy counts. | Has many accommodation_units, evacuation_records, center_issue_reports, and resource_requests |
| accommodation_types | Lookup table for unit types (Family Room, Dormitory, Tent, Classroom, Gymnasium, Covered Court). | Has many accommodation_units |
| accommodation_units | Individual rooms, tents, or halls inside an evacuation center with a specific unit type and max capacity. | Belongs to evacuation_centers and accommodation_types; has many unit_allocations |
| unit_allocations | Records the assignment of an evacuated household (evacuation_id) to a specific accommodation unit (unit_id). | Belongs to evacuation_records, accommodation_units, and users (assigned_by) |
| evacuation_records | Records a household's check-in admission to an evacuation center during a disaster event, including evacuated_count and checkout_at. | Belongs to households, evacuation_centers, disaster_events, and household_statuses; has many evacuated_members |
| evacuated_members | Tracks each individual family member's presence status (Evacuated vs. Not Evacuated) for a specific evacuation record. | Belongs to evacuation_records, household_members, and household_statuses |
| center_issue_reports, center_issue_categories & center_issue_report_statuses | Stores facility, medical, sanitation, and security issues reported at an evacuation center along with severity and resolution status. | Belongs to evacuation_centers and users (reported_by) |
| resource_requests & resource_request_statuses | Stores supply and personnel assistance requests submitted by centers and routed to ResQperation. | Belongs to evacuation_centers and users (requested_by) |
| notifications, notification_recipients, notification_logs & device_tokens | Stores SMS and Push broadcast alerts, target household recipients, device push tokens, and delivery logs. | Belongs to evacuation_centers and disaster_events; links to households |
| analytics, analytics_job_logs & analytics_job_statuses | Stores computed demographic and shelter occupancy snapshots and background analytics job logs. | Belongs to evacuation_centers and disaster_events |

Data Integrity & Archival: Foreign key constraints maintain referential integrity across all related tables. Soft deletes (deleted_at) are used on core records so historical evacuation data and analytics remain intact.
# Appendix A. Entity-Relationship Overview

The EvaTrack database centers around the evacuation_records table, which connects a SafeTrack household (households) to an evacuation shelter (evacuation_centers) under a declared disaster event (disaster_events). When a household is verified, individual family members are recorded in evacuated_members, and the center's live headcount is updated in center_occupancies. Within each shelter, living spaces are organized into accommodation_units (typed by accommodation_types) and assigned to households through unit_allocations. Operational needs at each shelter are tracked through center_issue_reports and resource_requests, while public advisories are recorded in notifications, notification_recipients, and notification_logs. The complete Entity-Relationship Diagram (ERD) is documented in evatrack_erd_reference.md.
# Appendix B. Requirements Traceability Matrix

The table below maps each Must-Have requirement from EVATRACK-Must-Haves (1).docx to its Functional Requirement ID, the actual backend and frontend files in the EvaTrack codebase, and the responsible team member:

| Baseline Must-Have Requirement | FR Mapping | Actual System Module (Backend & Frontend) | Proponent |
| --- | --- | --- | --- |
| Retrieve SafeTrack demographic data for identification & verification only; data hidden from households. | FR-STK-01<br>FR-STK-03<br>FR-STK-04 | HouseholdController.php<br>SearchHouseholdsAction.php<br>ExternalHouseholdService.php | Vhenz Cernal |
| Track household evacuation status as Evacuated or Not Evacuated. | FR-VER-04<br>FR-STAY-04 | EvacuationController.php<br>UpdateEvacuatedMemberStatusAction.php<br>HouseholdDetail.jsx | Vhenz Cernal |
| Automatically update evacuation center capacity when household is verified. | FR-CTR-04<br>FR-STAY-06 | ScanQREvacuationAction.php<br>VerifyManualEvacuationAction.php<br>CenterOccupancy.php | Vhenz Cernal |
| Compute evacuation analytics from demographics (age, pregnant, PWD, gender, affected pop). | FR-ANL-01<br>to FR-ANL-07 | AnalyticsController.php<br>LiveAnalyticsService.php<br>Analytics.jsx | Klint M. Ruales |
| Route alerts and assistance requests to households and ResQperation. | FR-RES-02<br>FR-NOTIF-02<br>FR-NOTIF-03 | NotificationService.php<br>TextBeeService.php<br>OneSignalService.php | Anna Rhea Villadolid |
| Evacuation Personnel: Log in (evacuation personnel role). | FR-AUTH-01<br>FR-AUTH-02<br>FR-AUTH-03 | AuthController.php<br>Login.jsx<br>useUserStore.ts / roles.ts | Anna Rhea Villadolid |
| Verify evacuated households via QR code scanning or manual input. | FR-VER-01<br>FR-VER-02<br>FR-VER-03 | ScanQREvacuationAction.php<br>VerifyManualEvacuationAction.php<br>VerifyHousehold.jsx | Klint M. Ruales |
| Update household info post-verification: assign units, monitor stay, update status. | FR-STAY-01<br>to FR-STAY-05 | UnitAllocationController.php<br>AssignUnitAllocationAction.php<br>CheckoutEvacuationAction.php<br>EvacuationDetail.jsx | Danica Gelbolingo |
| Report in-center needs (facility issues, resource shortages, and personnel requests). | FR-ISS-01<br>FR-RES-01 | CenterIssueReportController.php<br>CreateCenterIssueReportAction.php<br>CenterIssueReports.jsx | Anna Rhea Villadolid |
| Request resources and personnel assistance from ResQperation. | FR-RES-01<br>FR-RES-02<br>FR-RES-03 | ResourceRequestController.php<br>CreateResourceRequestAction.php<br>ResourceRequests.jsx | Anna Rhea Villadolid |
| Evacuation Admin: Log in (evacuation admin role). | FR-AUTH-01<br>FR-AUTH-02 | AuthController.php<br>Login.jsx<br>useUserStore.ts / roles.ts | Anna Rhea Villadolid |
| Create and configure evacuation centers. | FR-CTR-01<br>FR-CTR-05 | EvacuationCenterController.php<br>CreateEvacuationCenterAction.php<br>EvacuationList.jsx | Vhenz Cernal |
| Create and configure accommodation units (rooms/tents/halls) within an evacuation center. | FR-CTR-02<br>FR-CTR-03 | AccommodationUnitController.php<br>CreateAccommodationUnitAction.php<br>UnitModal.jsx | Danica Gelbolingo |
| Monitor ongoing evacuations across centers (status, occupancy, personnel activity). | FR-CTR-05<br>FR-EVT-03 | EvacuationCenterController.php<br>Dashboard.jsx<br>EvacuationList.jsx | Klint M. Ruales |
| Monitor ongoing requests per evacuation center. | FR-RES-03<br>FR-RES-05 | ListResourceRequestsAction.php<br>ResourceRequests.jsx | Anna Rhea Villadolid |
| Send evacuation alerts (SMS via TextBee for non-internet; push via OneSignal/ResQperation) and generate EVATRACK ADVISORY redirection alerts. | FR-NOTIF-01<br>to FR-NOTIF-06 | NotificationController.php<br>NotificationService.php<br>EvacuationAlerts.jsx | Klint M. Ruales |
| Super Admin: User registration, system-wide reports, full oversight, and emergency override access. | FR-AUTH-04<br>FR-OVR-01<br>to FR-OVR-04 | UserController.php<br>AnalyticsExportController.php<br>UserManagement.jsx | Klint M. Ruales |

# Appendix C. Capstone Approval & Sign-Off Sheet

By signing below, the capstone adviser, project coordinator, panel evaluators, and student proponents attest that this Software Requirements Specification has been reviewed and approved as the official baseline for EVATRACK (Version 1.0).

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


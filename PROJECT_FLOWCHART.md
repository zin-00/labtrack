# LabTrack System - Complete Process Flowchart

## LabTrack Operational Flow - Thesis Project
### Computer Laboratory Management System with RFID Access Control

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#ffcccc'}}}%%
flowchart TD
    Start([System Start]) --> Login[Admin/Faculty Login]
    Login --> Dashboard[Dashboard]
    
    Dashboard --> Setup[Laboratory Setup]
    Dashboard --> Register[Student Registration]
    Dashboard --> Assignment[Workstation Assignment]
    Dashboard --> Access[RFID Access Control]
    Dashboard --> Monitor[Real-time Monitoring]
    Dashboard --> Reports[Generate Reports]
    
    %% Laboratory Setup
    Setup --> S1[Create Laboratory]
    S1 --> S2[Register Computers]
    S2 --> S3[Assign to Lab]
    S3 --> S4[(Lab Database)]
    S4 --> Dashboard
    
    %% Student Registration
    Register --> R1[Enter Student Info]
    R1 --> R2[Assign RFID Card]
    R2 --> R3[(Student Database)]
    R3 --> Dashboard
    
    %% Workstation Assignment
    Assignment --> A1[Select Lab & Students]
    A1 --> A2[Select Computers]
    A2 --> A3{Assignment Mode?}
    A3 -->|Sequential| A4[One-to-One]
    A3 -->|Random| A5[Randomize]
    A3 -->|Many-to-Many| A6[Multiple]
    A4 --> A7[Create Assignments]
    A5 --> A7
    A6 --> A7
    A7 --> A8[(Assignment Database)]
    A8 --> Dashboard
    
    %% RFID Access Control
    Access --> AC1[Student Scans RFID]
    AC1 --> AC2{Verify Student}
    AC2 -->|Invalid| Deny[❌ Access Denied]
    AC2 -->|Valid| AC3{Has Assignment?}
    AC3 -->|No| Deny
    AC3 -->|Yes| AC4{Correct Lab?}
    AC4 -->|No| Deny
    AC4 -->|Yes| Unlock[✅ Unlock Computer]
    Unlock --> AC5[Log Activity]
    AC5 --> AC6[(Activity Database)]
    Deny --> Dashboard
    AC6 --> Dashboard
    
    %% Real-time Monitoring
    Monitor --> M1[View Computer Status]
    M1 --> M2[View Active Sessions]
    M2 --> M3[WebSocket Updates]
    M3 --> Dashboard
    
    %% Reports Generation
    Reports --> RP1{Select Report Type}
    RP1 -->|Usage Logs| RP2[Computer Logs]
    RP1 -->|Activities| RP3[Student Activities]
    RP1 -->|Assignments| RP4[Assignment List]
    RP2 --> RP5{Export?}
    RP3 --> RP5
    RP4 --> RP5
    RP5 -->|View| RP6[Display Table]
    RP5 -->|PDF| RP7[Generate PDF]
    RP6 --> Dashboard
    RP7 --> Dashboard
    
    Dashboard --> Logout{Logout?}
    Logout -->|No| Dashboard
    Logout -->|Yes| End([System End])
    
    %% Styling
    style Start fill:#90EE90
    style End fill:#FFB6C6
    style Dashboard fill:#87CEEB
    style Unlock fill:#98FB98
    style Deny fill:#FFA07A
    style S4 fill:#DDA0DD
    style R3 fill:#DDA0DD
    style A8 fill:#DDA0DD
    style AC6 fill:#DDA0DD
```

---

## Thesis Core Operations Summary

### 1️⃣ **Laboratory Setup**
- Create and configure computer laboratories
- Auto-register computers via network detection (IP/MAC)
- Assign computers to specific laboratories
- Enable real-time heartbeat monitoring (online/offline status)

### 2️⃣ **Student Registration**
- Register students with complete information (Name, ID, Program, Year, Section)
- Assign RFID cards to students
- Link RFID UID to student database records

### 3️⃣ **Workstation Assignment**
- Bulk assignment interface for efficient management
- Multiple assignment modes:
  - **Sequential**: One-to-one pairing
  - **Random**: Randomized distribution
  - **Many-to-Many**: Each student can access multiple computers
- Real-time validation and duplicate checking
- WebSocket broadcasting for instant UI updates

### 4️⃣ **RFID Access Control** (Core Thesis Feature)
- Student scans RFID card at computer
- System validates:
  - ✅ Student exists in database
  - ✅ Student has computer assignment
  - ✅ Assignment is for current laboratory
- Automatic computer unlock on successful validation
- Activity logging (who, what, when, where)
- Real-time dashboard updates

### 5️⃣ **Real-time Monitoring**
- Live computer status (online/offline/locked)
- Active student sessions tracking
- WebSocket-based instant updates
- Laboratory occupancy overview

### 6️⃣ **Reports & Analytics**
- Computer usage logs
- Student activity tracking
- Browser history monitoring
- Assignment reports
- Filtered PDF exports with complete data

---

## Key Technologies

- **Frontend**: Vue.js 3 (Composition API), Pinia State Management
- **Backend**: Laravel 11 (PHP), RESTful API
- **Database**: MySQL (Eloquent ORM)
- **Real-time**: Laravel Reverb/Pusher (WebSocket)
- **Authentication**: Laravel Sanctum (Token-based)
- **Hardware**: RFID Readers (RC522/EM18)
- **PDF Export**: jsPDF with autoTable

## System Overview (Simplified)

```mermaid
graph TB
    Start([LabTrack System]) --> Auth[Authentication System]
    Start --> CompMgmt[Computer Management]
    Start --> StudentMgmt[Student Management]
    Start --> LabMgmt[Laboratory Management]
    Start --> Assignment[Workstation Assignment]
    Start --> Monitoring[Real-time Monitoring]
    Start --> Reporting[Reports & Analytics]
```

## 1. Authentication & Authorization Flow

```mermaid
graph TB
    A[User Login] --> B{Valid Credentials?}
    B -->|No| C[Show Error]
    C --> A
    B -->|Yes| D[Generate Sanctum Token]
    D --> E{User Role?}
    E -->|Admin| F[Admin Dashboard]
    E -->|Faculty| G[Faculty Dashboard]
    F --> H[Full System Access]
    G --> I[Limited Access]
    H --> J[Profile Management]
    I --> J
    J --> K[Update Password]
    J --> L[Update Profile Info]
```

## 2. Computer Management Flow

```mermaid
graph TB
    A[Computer Management] --> B[Register Computer]
    A --> C[Assign to Laboratory]
    A --> D[Monitor Status]
    A --> E[Unlock Computer]
    
    B --> B1[Auto-register via IP/MAC]
    B1 --> B2[Check if exists]
    B2 -->|Exists| B3[Update existing]
    B2 -->|New| B4[Create new record]
    
    C --> C1[Select Computers]
    C1 --> C2[Choose Laboratory]
    C2 --> C3[Bulk Assign]
    C3 --> C4[Update laboratory_id]
    
    D --> D1[Heartbeat System]
    D1 --> D2{Last heartbeat < 5min?}
    D2 -->|Yes| D3[Mark Online]
    D2 -->|No| D4[Mark Offline]
    
    E --> E1[RFID Scan]
    E1 --> E2{Student Found?}
    E2 -->|No| E3[Access Denied]
    E2 -->|Yes| E4{Has Assignment?}
    E4 -->|No| E5[Not Assigned]
    E4 -->|Yes| E6[Unlock Computer]
    E6 --> E7[Create Activity Log]
    E7 --> E8[Broadcast Event]
```

## 3. Student Management Flow

```mermaid
graph TB
    A[Student Management] --> B[CRUD Operations]
    A --> C[Import Students]
    A --> D[Assign RFID]
    A --> E[Filter & Search]
    
    B --> B1[Create Student]
    B --> B2[Update Student]
    B --> B3[Delete Student]
    
    B1 --> B4[Validate Data]
    B4 --> B5[Set Program/Year/Section]
    B5 --> B6[Save to Database]
    
    C --> C1[Upload CSV/Excel]
    C1 --> C2[Validate Format]
    C2 --> C3[Process Each Row]
    C3 --> C4[Check Duplicates]
    C4 -->|Exists| C5[Skip/Update]
    C4 -->|New| C6[Insert Record]
    
    E --> E1[Filter by Program]
    E --> E2[Filter by Year Level]
    E --> E3[Filter by Section]
    E --> E4[Search by Name/ID]
    E4 --> E5[Backend Query]
    E5 --> E6[Return Paginated Results]
```

## 4. Workstation Assignment Flow (Bulk Assignment)

```mermaid
graph TB
    A[Open Bulk Assignment Modal] --> B[Load Available Students]
    A --> C[Load Available Computers]
    
    B --> B1[Apply Filters]
    B1 --> B2[Program Filter]
    B1 --> B3[Year Level Filter]
    B1 --> B4[Section Filter]
    B1 --> B5[Status Filter]
    B1 --> B6[Laboratory Filter]
    B6 --> B7[Fetch Unassigned Students]
    
    C --> C1[Laboratory Filter]
    C1 --> C2[Search Filter]
    C2 --> C3[Fetch Available Computers]
    
    B7 --> D[Select Students]
    C3 --> E[Select Computers]
    
    D --> F{Both Selected?}
    E --> F
    
    F -->|No| G[Show Error]
    F -->|Yes| H[Choose Assignment Mode]
    
    H --> H1{Mode?}
    H1 -->|Sequential| I[Student1→PC1, Student2→PC2]
    H1 -->|Random| J[Shuffle & Assign]
    
    I --> K[Create Assignments]
    J --> K
    
    K --> K1[Many-to-Many Assignment]
    K1 --> K2[Each Student × Each Computer]
    K2 --> K3{Already Assigned?}
    K3 -->|Yes| K4[Skip Assignment]
    K3 -->|No| K5[Create computer_students Record]
    K5 --> K6[Set laboratory_id]
    K6 --> K7[Bulk Insert]
    
    K7 --> L[Clear Selections]
    L --> M[Refresh Lists]
    M --> N[Keep Modal Open]
    N --> O{Continue?}
    O -->|Yes| D
    O -->|No| P[Close Modal]
```

## 5. Real-time Monitoring Flow

```mermaid
graph TB
    A[Real-time Monitoring] --> B[Computer Heartbeat]
    A --> C[WebSocket Events]
    A --> D[Status Updates]
    
    B --> B1[Computer Sends Heartbeat]
    B1 --> B2[Update last_seen]
    B2 --> B3[Check Status]
    B3 --> B4{Online?}
    B4 -->|Yes| B5[Broadcast Online Event]
    B4 -->|No| B6[Broadcast Offline Event]
    
    C --> C1[Laravel Reverb/Pusher]
    C1 --> C2[Listen on Channels]
    C2 --> C3[computer-event]
    C2 --> C4[config-event]
    C2 --> C5[student-event]
    
    C3 --> C6[Update Computer UI]
    C4 --> C7[Update Assignment UI]
    C5 --> C8[Update Student UI]
    
    D --> D1[Computer Status Changed]
    D1 --> D2[Frontend Receives Event]
    D2 --> D3[Update Component State]
    D3 --> D4[Re-render UI]
```

## 6. Computer Unlock Flow (RFID System)

```mermaid
graph TB
    A[Student Scans RFID] --> B[Computer Receives RFID]
    B --> C[Send to Backend API]
    C --> D{Find Student by RFID?}
    
    D -->|Not Found| E[Return Error 404]
    E --> F[Show 'Student Not Found']
    
    D -->|Found| G{Has Computer Assignment?}
    
    G -->|No| H[Return Error 404]
    H --> I[Show 'No Assignment']
    
    G -->|Yes| J{Assignment in This Lab?}
    
    J -->|No| K[Wrong Laboratory]
    K --> L[Access Denied]
    
    J -->|Yes| M[Get Assigned Computers]
    M --> N[Unlock All Computers]
    
    N --> N1[Set is_lock = false]
    N1 --> N2[Create Computer Log]
    N2 --> N3[Log: student_id, computer_id]
    N3 --> N4[Log: start_time, program, year_level]
    
    N4 --> O[Broadcast Events]
    O --> O1[ScanEvent]
    O --> O2[ComputerUnlockRequested]
    
    O2 --> P[Frontend Receives Event]
    P --> Q[Update Computer Status]
    Q --> R[Show in Recent Scans]
    R --> S[Display Student Info]
```

## 7. Laboratory Management Flow

```mermaid
graph TB
    A[Laboratory Management] --> B[Create Laboratory]
    A --> C[Assign Computers]
    A --> D[Unassign Computers]
    A --> E[View Lab Status]
    
    B --> B1[Enter Lab Details]
    B1 --> B2[Name, Code, Description]
    B2 --> B3[Save to Database]
    
    C --> C1[Assign Mode]
    C1 --> C2[Select Unassigned Computers]
    C2 --> C3[Bulk Select]
    C3 --> C4[Assign to Laboratory]
    C4 --> C5[Update laboratory_id]
    C5 --> C6[Create Audit Log]
    
    D --> D1[Unassign Mode]
    D1 --> D2[Select Assigned Computers]
    D2 --> D3[Bulk Select]
    D3 --> D4[Set laboratory_id = NULL]
    D4 --> D5[Create Audit Log]
    
    E --> E1[Show Computer Count]
    E --> E2[Online/Offline Status]
    E --> E3[Active Assignments]
```

## 8. Reports & PDF Export Flow

```mermaid
graph TB
    A[Generate Report] --> B[Apply Filters]
    B --> B1[Program Filter]
    B --> B2[Laboratory Filter]
    B --> B3[Year Level Filter]
    B --> B4[Section Filter]
    B --> B5[Search Query]
    
    B5 --> C[Fetch ALL Filtered Data]
    C --> C1[Backend Query]
    C1 --> C2{Export Type?}
    
    C2 -->|Paginated| C3[Return 10 Records]
    C2 -->|All| C4[Return All Records]
    
    C4 --> D[Generate PDF]
    D --> D1[Create jsPDF Instance]
    D1 --> D2[Add Header/Title]
    D2 --> D3[Add Filter Information]
    D3 --> D4[Add Total Records Count]
    D4 --> D5[Generate Table with autoTable]
    D5 --> D6[Add Data Rows]
    D6 --> D7[Apply Styling]
    D7 --> D8[Download PDF File]
    D8 --> D9[Show Success Message]
```

## 9. Activity Logging & Audit Flow

```mermaid
graph TB
    A[User Action] --> B{Action Type?}
    
    B -->|Computer| C1[Computer Log]
    B -->|Student| C2[Activity Log]
    B -->|Browser| C3[Browser Activity]
    B -->|System| C4[Audit Log]
    
    C1 --> D1[Log Computer Unlock]
    D1 --> D2[student_id, computer_id]
    D2 --> D3[start_time, end_time]
    D3 --> D4[program, year_level]
    
    C2 --> E1[Log Student Activity]
    E1 --> E2[action, description]
    E2 --> E3[old_data, new_data]
    
    C3 --> F1[Log Browser Activity]
    F1 --> F2[URL visited]
    F2 --> F3[Duration]
    
    C4 --> G1[Log Admin Action]
    G1 --> G2[user_id, action]
    G2 --> G3[ip_address]
    G3 --> G4[old_data, new_data]
    G4 --> G5[description]
```

## 10. Data Sync & Broadcasting Flow

```mermaid
graph TB
    A[Database Change] --> B[Eloquent Event]
    B --> C{Event Type?}
    
    C -->|Created| D[Broadcast 'add' Event]
    C -->|Updated| E[Broadcast 'update' Event]
    C -->|Deleted| F[Broadcast 'delete' Event]
    
    D --> G[Laravel Reverb/Pusher]
    E --> G
    F --> G
    
    G --> H[WebSocket Server]
    H --> I[Connected Clients]
    
    I --> J[Frontend Echo Listener]
    J --> K{Channel?}
    
    K -->|computer-event| L1[Update Computer List]
    K -->|config-event| L2[Update Assignment List]
    K -->|student-event| L3[Update Student List]
    
    L1 --> M[Update Component State]
    L2 --> M
    L3 --> M
    
    M --> N[Re-render UI]
    N --> O[Show Updated Data]
```

## 11. Search & Filter System

```mermaid
graph TB
    A[User Input] --> B[Debounce 300ms]
    B --> C[Build Query Parameters]
    
    C --> D[Frontend Filters]
    D --> D1[Search Query]
    D --> D2[Program Filter]
    D --> D3[Year Level Filter]
    D --> D4[Section Filter]
    D --> D5[Laboratory Filter]
    D --> D6[Status Filter]
    
    D6 --> E[Send to Backend]
    E --> F[Backend Query Builder]
    
    F --> F1{Has Search?}
    F1 -->|Yes| F2[WHERE LIKE search]
    F1 -->|No| F3[Skip]
    
    F2 --> G{Has Filters?}
    F3 --> G
    
    G -->|Yes| H[Apply WHERE Clauses]
    G -->|No| I[Skip]
    
    H --> J[Apply Relationships]
    I --> J
    
    J --> J1[with computer.laboratory]
    J --> J2[with student.program]
    J --> J3[with student.year_level]
    J --> J4[with student.section]
    
    J4 --> K[Order By]
    K --> L{Paginate?}
    
    L -->|Yes| M[paginate 10]
    L -->|No| N[get All]
    
    M --> O[Return JSON Response]
    N --> O
    
    O --> P[Frontend Receives Data]
    P --> Q[Update Component State]
    Q --> R[Render Table/List]
```

## 12. System Architecture Overview

```mermaid
graph TB
    subgraph Frontend [Frontend - Vue.js]
        A1[Vue Components]
        A2[Pinia Stores]
        A3[Vue Router]
        A4[Axios HTTP Client]
        A5[Echo WebSocket]
    end
    
    subgraph Backend [Backend - Laravel]
        B1[API Routes]
        B2[Controllers]
        B3[Models Eloquent]
        B4[Database MySQL]
        B5[Broadcasting Reverb]
        B6[Sanctum Auth]
    end
    
    subgraph External [External Systems]
        C1[Computer Agents]
        C2[RFID Readers]
        C3[PDF Generator]
    end
    
    A1 --> A4
    A4 --> B1
    B1 --> B2
    B2 --> B3
    B3 --> B4
    
    B2 --> B5
    B5 --> A5
    A5 --> A1
    
    A4 --> B6
    
    C1 --> B1
    C2 --> B1
    A1 --> C3
    
    A2 --> A1
    A3 --> A1
```

## 13. Complete User Journey - Student Assignment

```mermaid
graph TB
    Start([Admin/Faculty User]) --> Login[Login to System]
    Login --> Dashboard[View Dashboard]
    Dashboard --> NavWS[Navigate to Workstation Mapping]
    
    NavWS --> ViewList[View Current Assignments]
    ViewList --> ApplyFilter{Need Filtering?}
    
    ApplyFilter -->|Yes| Filter[Apply Filters]
    Filter --> Filter1[Select Program]
    Filter --> Filter2[Select Laboratory]
    Filter --> Filter3[Select Year Level]
    Filter --> Filter4[Select Section]
    Filter4 --> ViewFiltered[View Filtered List]
    
    ApplyFilter -->|No| Decision{What Action?}
    ViewFiltered --> Decision
    
    Decision -->|View Details| ViewDetail[View Assignment Details]
    Decision -->|Unassign| Unassign[Unassign Student]
    Decision -->|Bulk Unassign| BulkUnassign[Select Multiple & Unassign]
    Decision -->|Bulk Assign| BulkAssign[Open Bulk Assignment Modal]
    Decision -->|Export PDF| ExportPDF[Generate & Download PDF]
    
    BulkAssign --> SelectLab[Select Laboratory]
    SelectLab --> SelectStudents[Select Students]
    SelectStudents --> SelectPCs[Select Computers]
    SelectPCs --> ChooseMode[Choose Assignment Mode]
    ChooseMode --> Mode1[Sequential]
    ChooseMode --> Mode2[Random]
    Mode1 --> CreateAssign[Create Assignments]
    Mode2 --> CreateAssign
    CreateAssign --> Success[Show Success Message]
    Success --> ClearSel[Clear Selections]
    ClearSel --> KeepModal{Continue Assigning?}
    KeepModal -->|Yes| SelectStudents
    KeepModal -->|No| CloseModal[Close Modal]
    
    ExportPDF --> FetchAll[Fetch All Filtered Data]
    FetchAll --> GenPDF[Generate PDF Document]
    GenPDF --> Download[Download PDF]
    
    ViewDetail --> End([Done])
    Unassign --> End
    BulkUnassign --> End
    Download --> End
    CloseModal --> End
```

## 14. Database Schema Relationships

```mermaid
erDiagram
    USERS ||--o{ AUDIT_LOGS : creates
    STUDENTS ||--o{ COMPUTER_STUDENTS : has
    COMPUTERS ||--o{ COMPUTER_STUDENTS : assigned_to
    LABORATORIES ||--o{ COMPUTERS : contains
    PROGRAMS ||--o{ STUDENTS : belongs_to
    YEAR_LEVELS ||--o{ STUDENTS : belongs_to
    SECTIONS ||--o{ STUDENTS : belongs_to
    STUDENTS ||--o{ COMPUTER_LOGS : generates
    COMPUTERS ||--o{ COMPUTER_LOGS : tracks
    COMPUTERS ||--o{ BROWSER_ACTIVITIES : records
    
    STUDENTS {
        int id PK
        string student_id UK
        string rfid_uid UK
        string first_name
        string last_name
        int program_id FK
        int year_level_id FK
        int section_id FK
        string status
    }
    
    COMPUTERS {
        int id PK
        string computer_number UK
        string ip_address
        string mac_address
        int laboratory_id FK
        boolean is_online
        boolean is_lock
        timestamp last_seen
    }
    
    COMPUTER_STUDENTS {
        int id PK
        int student_id FK
        int computer_id FK
        int laboratory_id FK
        timestamp created_at
    }
    
    LABORATORIES {
        int id PK
        string name
        string code
        text description
    }
    
    PROGRAMS {
        int id PK
        string program_name
        string program_code
    }
    
    COMPUTER_LOGS {
        int id PK
        int student_id FK
        int computer_id FK
        string program
        string year_level
        timestamp start_time
        timestamp end_time
    }
```

---

## Summary

This flowchart system covers:

1. **Authentication** - Login, role-based access, profile management
2. **Computer Management** - Registration, assignment, monitoring, unlocking
3. **Student Management** - CRUD, import, RFID assignment, filtering
4. **Workstation Assignment** - Bulk many-to-many assignments with filters
5. **Real-time Monitoring** - WebSocket events, status updates
6. **RFID Unlock System** - Student authentication, computer unlock
7. **Laboratory Management** - Lab creation, computer assignment
8. **Reports & Export** - Filtered PDF generation
9. **Activity Logging** - Comprehensive audit trails
10. **Data Broadcasting** - Real-time UI updates
11. **Search & Filter** - Debounced backend queries
12. **System Architecture** - Frontend/Backend integration
13. **User Journey** - Complete workflow example
14. **Database Schema** - Entity relationships

The system is designed as a **real-time computer laboratory management system** with RFID-based access control, comprehensive assignment management, and detailed activity tracking.

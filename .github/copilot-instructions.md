# Copilot Instructions

## 1. Authoritative specification

`BUILD_SPEC.md` is the repository's implementation specification, and it is derived from the primary project architecture described in `Laravel Project Specification.docx`.

Before creating, modifying, refactoring, removing, or testing application code:

1. Read the relevant `BUILD_SPEC.md` sections.
2. Inspect the existing project structure and conventions.
3. Preserve the architectural boundaries and domain invariants below.
4. Prefer the smallest Laravel-native change that satisfies the requirement.
5. Add or update tests for important behavior.
6. Run the relevant tests and verify the application still boots.

Do not silently contradict, weaken, or replace the architecture.

If a requirement is ambiguous, choose the simplest solution consistent with the specification and existing Laravel conventions. If the requirement cannot be implemented reliably without a major architectural change, document the technical issue and proposed alternative before introducing it.

---

## 2. Core technology architecture

The application is fundamentally a Laravel/Blade/Livewire application:

- Laravel 13
- PHP 8.4
- Laravel Livewire starter kit
- Blade
- Tailwind CSS
- Alpine.js where useful
- Vite
- MariaDB 11.x
- GitHub Codespaces
- Dev Container
- Docker Compose

Specialized browser functionality is deliberately isolated:

- Konva.js for interactive 2D blueprint visualization
- IndexedDB through `idb` for offline field persistence
- Service Worker/PWA functionality
- Small JavaScript modules for offline synchronization
- QR-code generation/preview/printing orchestration
- Browser APIs

Do **not** introduce:

- React
- Vue
- Svelte
- Inertia
- Next.js
- Firebase
- MongoDB
- a separate Node backend
- another frontend framework
- a second application/backend architecture

Do not add Composer or npm dependencies unless the project specification explicitly permits them or a concrete technical requirement is documented.

The application must not become a SPA.

---

## 3. Authoritative data architecture

MariaDB is authoritative.

IndexedDB is an offline client-side store only. It is never a replacement for MariaDB and must never be treated as the authoritative database.

The principal server architecture is:

```text
Laravel 13
├── Blade
├── Livewire
└── Sync API
        |
Application Services
├── Eloquent
├── Laravel Data
└── Policies
        |
MariaDB 11.x
(authoritative)
```

Field/browser architecture:

```text
Browser / PWA
├── Blade
├── Livewire
└── JavaScript
    ├── Konva.js
    ├── IndexedDB / idb
    └── Service Worker
```

Keep responsibilities separated. JavaScript orchestrates browser concerns; Laravel owns business rules, persistence, validation, authorization, synchronization decisions, and transactions.

---

## 4. Critical domain invariants

These are non-negotiable unless an explicit architectural decision changes the specification.

### Placement

A `Placement` is the authoritative physical managed position.

A Placement:

- belongs to a Floor;
- has a stable UUID;
- has a stable code;
- owns physical coordinates;
- may have zero or one assigned Extinguisher;
- may represent an installed or reserve position.

Reserve inventory is represented by individual reserve Placements, not by a special multi-extinguisher container.

### Extinguisher

An `Extinguisher` is a physical piece of equipment.

Equipment-specific data belongs to the Extinguisher, including:

- serial number;
- type;
- manufacturer/brand;
- physical capacity;
- extinguishing capacity;
- maintenance seal;
- status;
- last inspection;
- next refill;
- next maintenance;
- equipment history and notes.

The Extinguisher does **not** own blueprint coordinates.

An Extinguisher may temporarily have no Placement.

Use stable UUIDs for entities that participate in offline synchronization. Offline-created entities must not require an auto-increment ID from MariaDB before creation.

### Placement/Extinguisher relationship

The physical relationship is:

```text
Building
└── Floor
    └── Placement
        └── 0..1 Extinguisher
```

Prefer `extinguishers.placement_id` as the relationship because an Extinguisher can temporarily be unassigned.

The database must enforce that a Placement cannot have more than one Extinguisher. A nullable `placement_id` with a uniqueness constraint is the intended model.

### Swapping

A swap changes which Extinguisher is assigned to an existing Placement.

A swap must not change:

- Placement identity;
- Placement UUID;
- Placement code;
- Placement QR identity;
- Placement physical coordinates;

The physical extinguisher remains the same Extinguisher record, with its serial number, type, manufacturer, capacity, extinguishing capacity, maintenance seal, and history attached to it.

Swaps involving multiple records must be authorized and transactional.

Do not invent a separate replacement-history domain feature merely to model swaps. Use the general audit trail where history is required.

---

## 5. Blueprint and virtual-twin rules

Blueprints visualize Placements and their currently assigned Extinguishers.

Physical `x`/`y` coordinates belong to the Placement and are authoritative real-world position data.

Konva overlap/cluster offsets are derived display state only.

Never persist temporary cluster offsets as Placement coordinates.

Never allow zooming, panning, marker sizing, clustering, or screen-space calculations to mutate authoritative coordinates.

A Placement can be rendered even when it has no Extinguisher.

### Konva boundary

The Konva stage is JavaScript-owned state.

Livewire must not directly manipulate the Konva scene graph.

Use a JavaScript bridge/event mechanism:

```text
Livewire
  -> application state/events
  -> JavaScript bridge
  -> Konva

Konva
  -> click/drag/selection
  -> JavaScript
  -> Livewire/application event
  -> Laravel
```

Keep Konva internals inside `resources/js/blueprint/`.

Suggested modules:

```text
resources/js/blueprint/
├── BlueprintCanvas.js
├── PlacementMarker.js
├── ExtinguisherMarker.js
├── BlueprintEditor.js
└── overlap.js
```

### Positioned vs unpositioned Placements

Maintain separate positioned and unpositioned Placement datasets.

- Only positioned Placements are sent to the Konva marker renderer.
- Unpositioned Placements appear in a sidebar.
- New positioning starts as temporary Konva state.
- Authorized users must explicitly enter Placement Editing mode.
- A drag creates a proposed position.
- Require confirmation before persisting `x`/`y`.
- After successful persistence, move the Placement into the positioned dataset.
- Offline proposed coordinate changes may become pending authorized operations in IndexedDB.

Normal viewing mode must not make Placements draggable.

---

## 6. Spatie package responsibilities

Use the selected Spatie packages for their intended responsibilities.

### Spatie Laravel Permission

Authoritative role/permission system.

Initial roles:

- Administrator
- Technician
- Maintenance
- Viewer

Initial permissions include the project specification's permissions for users, buildings, floors, Placements, blueprints, extinguishers, inspections, maintenance, QR codes, reports, documents, audit, and synchronization.

Use Spatie Permission together with Laravel Policies.

Never trust roles, permissions, IDs, relationships, or authorization decisions supplied by the browser.

Do not create custom role/permission tables when Spatie already provides them.

### Spatie Laravel Media Library

Use Media Library for:

- blueprint PDFs;
- inspection photos;
- documents;
- other explicitly supported application media.

Use media collections such as:

- `blueprint`
- `documents`
- `inspection-photos`

Do not create parallel `media` or `inspection_photos` tables merely to duplicate Media Library functionality.

The original uploaded blueprint PDF remains the authoritative document.

Validate uploads server-side and do not trust browser MIME types.

### Spatie Laravel Activitylog

Use Activity Log for audit/history.

Audit important actions such as:

- user/role changes;
- Extinguisher creation/update/deletion;
- Placement creation/update/deletion;
- assignment changes;
- swaps;
- blueprint coordinate changes;
- inspection creation/update/approval;
- refill/maintenance changes;
- QR generation/printing where useful;
- batch operations;
- synchronization operations;
- conflict handling.

Do not create a parallel `audit_logs` table when Activitylog provides the required functionality.

Never log passwords, authentication secrets, or sensitive tokens.

### Spatie Laravel Data

Use `spatie/laravel-data` for typed application boundaries and structured data.

Good uses include:

```text
app/Data/Placement/PlacementData.php
app/Data/Extinguisher/ExtinguisherData.php
app/Data/Inspection/InspectionData.php
app/Data/Inspection/InspectionItemData.php
app/Data/Sync/SyncOperationData.php
app/Data/Sync/SyncUploadData.php
app/Data/Sync/SyncResponseData.php
app/Data/Sync/SyncAcknowledgementData.php
app/Data/Blueprint/PlacementPositionData.php
app/Data/QR/QrLabelData.php
app/Data/Batch/BatchExtinguisherData.php
```

Laravel Data does not replace:

- Eloquent;
- Form Requests;
- Policies;
- database constraints;
- domain/application services.

Use typed nested Data objects instead of unstructured arrays where practical.

---

## 7. Laravel application structure

Follow normal Laravel conventions.

Use:

- Eloquent models and relationships;
- Form Requests;
- Policies;
- Actions/services where business logic warrants them;
- Livewire components;
- Controllers for appropriate HTTP/API endpoints;
- Laravel Data DTOs;
- Events/listeners where useful;
- Jobs where asynchronous work is justified;
- Notifications where appropriate;
- migrations;
- factories;
- seeders;
- database transactions.

Suggested organization:

```text
app/
├── Actions/
├── Data/
│   ├── Blueprint/
│   ├── Batch/
│   ├── Extinguisher/
│   ├── Inspection/
│   ├── Placement/
│   ├── QR/
│   └── Sync/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Livewire/
├── Models/
├── Policies/
├── Services/
└── ...
```

Do not force this structure where Laravel defaults are preferable.

Keep business logic out of Blade templates and Livewire views.

Do not put database rules or business rules into JavaScript.

Inspect existing code before creating abstractions and extend existing implementations instead of creating duplicates.

---

## 8. Authentication and authorization

Start from the Laravel Livewire starter kit and use Laravel's supported authentication infrastructure.

Support, as provided by the selected starter-kit configuration:

- login;
- logout;
- registration;
- password reset;
- email verification;
- password/session security;
- two-factor authentication if provided;
- user profile/account functionality.

Never implement a custom password authentication system.

Offline operation must never bypass server authentication.

Offline data represents previously authorized field access. On synchronization Laravel must re-check:

- authenticated user;
- role/permissions;
- target building;
- target floor;
- target Placement;
- target Extinguisher;
- requested operation;
- submitted data;
- relationship validity;
- business rules.

A user must not gain access to another site's data by modifying IndexedDB.

Field-level restrictions must be enforced server-side. For example, permissions may allow updating maintenance seal/status/notes while prohibiting changes to protected equipment identity fields.

---

## 9. Localization

All user-facing text must be Brazilian Portuguese (`pt-BR`), including:

- navigation;
- forms;
- validation messages;
- notifications;
- buttons;
- errors;
- empty states;
- inspection UI;
- synchronization state;
- online/offline indicators.

Use Laravel localization facilities and `pt_BR` as the application locale.

Do not introduce new English user-facing text.

---

## 10. Offline-first architecture

Offline support is a first-class architectural requirement, not a final add-on.

Design UUIDs, synchronization, authorization, conflict handling, media upload, audit behavior, IndexedDB storage, and authentication assumptions around offline operation.

### Offline scope

Prioritize offline field work:

- assigned buildings/floors;
- relevant Placements;
- relevant blueprints;
- relevant Extinguishers;
- relevant inspection/reference data;
- inspection forms;
- inspection results;
- notes;
- photos where practical;
- pending changes.

Administrative operations may remain online unless a concrete offline requirement is established.

### IndexedDB

Use `idb`. Do not use `localStorage` as the primary offline database.

Suggested stores:

```text
user_context
buildings
floors
blueprints
placements
extinguishers
inspections
inspection_items
pending_changes
sync_metadata
```

Local synchronized records should retain enough metadata for:

- UUID;
- local creation/update state;
- server version where applicable;
- synchronization state;
- timestamps;
- deletion state;
- conflict state.

Offline media may be stored as browser Blobs where practical.

Avoid storing unnecessary sensitive data. Never store passwords, password hashes, long-lived server secrets, or authentication secrets.

Suggested modules:

```text
resources/js/offline/
├── db.js
├── queue.js
└── sync.js
```

`sync.js` orchestrates synchronization, queue processing, retries, online detection, and synchronization state. It must not contain Laravel business rules.

---

## 11. Synchronization rules

Use dedicated synchronization endpoints rather than turning the entire application into a REST API.

Example routes:

```text
GET  /api/sync/download
POST /api/sync/upload
POST /api/sync/acknowledge
```

The protocol must be explicit and versionable.

Each operation must identify enough information for:

- device/session context;
- entity UUID;
- entity type;
- operation;
- local version;
- local timestamp;
- payload;
- client-generated operation UUID;
- protocol version where appropriate.

Use Laravel Data objects for synchronization input/output.

### Idempotency

Every offline operation needs a unique client-generated operation UUID.

The server must recognize retries. Repeating an operation must not duplicate:

- inspections;
- extinguishers;
- assignments;
- swaps;
- Placement changes.

Maintain synchronization records sufficient to determine whether an operation has already been processed.

### Conflicts

Detect conflicts when the client version differs from the current server version or an equivalent authoritative check shows the record changed after download.

Never silently overwrite newer server state.

Initial conflict strategy:

1. Server wins conflicting authoritative fields.
2. Preserve the rejected client operation.
3. Record the conflict.
4. Inform the user.
5. Keep conflict resolution extensible.

Inspections should be append-oriented so multiple inspectors can normally create separate records without overwriting one another.

Synchronization changes must be transactional.

---

## 12. PWA and Service Worker

Implement basic PWA support:

- `manifest.json`;
- service worker;
- application-shell/static-resource caching;
- online/offline state detection.

Do not indiscriminately cache sensitive server responses.

Be conservative with authentication-related caching.

Offline domain data belongs in IndexedDB, not merely in service-worker HTTP cache.

The UI should communicate synchronization states in Portuguese, including:

- Online
- Offline
- Sincronização pendente
- Sincronização em andamento
- Sincronização concluída
- Falha na sincronização
- Conflito requer atenção

---

## 13. Inspection architecture

Inspection functionality consists of:

- execution;
- results;
- inspection items;
- notes;
- photos;
- history;
- approval where authorized.

**Inspection scheduling is explicitly out of scope.**

Do not implement:

- inspection due-date scheduling;
- inspection appointments;
- next inspection date;
- inspection calendars.

The inspection workflow must work online first and then offline.

Offline inspection creation must be saved locally and synchronized later.

Inspection photos use Media Library on the server and may use IndexedDB Blobs before synchronization where practical.

The basic inspection workflow must remain usable even if photo capture/storage is unavailable.

---

## 14. Dates and extinguisher data

Use native database-compatible temporal representations.

Required presentation/input formats:

- last inspection: `dd-mm-yyyy`
- next refill: `mm-yyyy`
- next maintenance: `yyyy`

Never store formatted date strings as the authoritative temporal value when an appropriate native database type is available.

Keep maintenance seal and extinguishing capacity as dedicated Extinguisher attributes. Do not confuse extinguishing capacity with physical capacity.

---

## 15. Blueprint files and media

Blueprints are application data with a model containing information such as:

- id;
- UUID;
- floor;
- name;
- file type;
- width;
- height;
- version;
- timestamps;
- updater;
- soft deletion where appropriate.

Use Spatie Media Library for the actual blueprint file rather than manually managing a `file_path` when the package provides the required functionality.

Support PDF at minimum.

The original PDF remains authoritative. PDF rendering must be isolated from the Konva scene graph.

---

## 16. QR codes

Support:

- individual QR codes;
- Placement QR;
- Extinguisher QR;
- label templates;
- preview;
- printing;
- batch QR generation;
- batch printing.

Placement QR identity must remain stable through Extinguisher swaps.

Keep QR generation/preview/printing orchestration in the QR JavaScript boundary where browser functionality is required; Laravel remains responsible for authoritative label data, permissions, and business rules.

Do not add a QR dependency unless the specified project dependencies or existing capabilities are insufficient and the need is documented.

---

## 17. Batch operations

Support batch creation and management of Extinguishers.

Batch workflows include:

- extinguisher templates;
- reserve Placement generation;
- validation;
- unique UUID generation;
- duplicate serial prevention;
- assignment;
- jobs where justified;
- progress/status;
- Activity Log.

Use transactions for multi-record operations where required.

Do not turn every batch action into an asynchronous job without a concrete reason.

---

## 18. Database rules

MariaDB 11.x is the authoritative relational database.

Use Laravel's MySQL/PDO driver.

Create proper:

- foreign keys;
- indexes;
- unique constraints;
- soft-delete behavior where appropriate.

Important indexes include, as appropriate:

- UUIDs;
- Placement codes;
- serial numbers;
- foreign keys;
- inspection dates;
- next refill date;
- next maintenance date;
- synchronization identifiers;
- updated/version fields.

The unique Placement/Extinguisher relationship must be enforced at the database level where practical.

Do not design production behavior around SQLite. Tests must not depend on SQLite-specific behavior.

---

## 19. Development environment

The development environment must be reproducible through GitHub Codespaces, Dev Container, and Docker Compose.

Expected structure:

```text
.devcontainer/
├── devcontainer.json
└── Dockerfile

compose.yaml
```

Provide:

- PHP 8.4;
- Composer 2;
- Node.js 22 LTS or an appropriate supported current LTS;
- npm;
- required PHP extensions;
- Git;
- useful development tooling;
- MariaDB 11.x as a separate Docker Compose service.

Do not install unnecessary system packages.

Expected initialization is approximately:

```text
docker compose up -d
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Codespaces initialization may automate safe steps, but must never be destructive.

Useful verification:

```text
php -v
composer --version
node --version
npm --version
docker compose ps
php artisan about
php artisan migrate:status
```

---

## 20. Dependency discipline

Use the Laravel Livewire starter kit's existing frontend setup.

Project-specific frontend additions are limited to the specified specialized packages, notably:

```text
konva
idb
```

A QR-code generation package may be added only if genuinely required and existing capabilities are insufficient.

Do not add:

- Redis initially unless required;
- Elasticsearch;
- Kafka;
- RabbitMQ;
- separate API server;
- separate frontend server;
- Kubernetes.

Potential future services are not initial dependencies merely because they may eventually be useful.

---

## 21. UI requirements

Build a clean responsive administrative interface with Blade, Livewire, and Tailwind.

Main navigation should be permission-aware and may include:

- Painel
- Plantas
- Extintores
- Edifícios
- Andares
- Inspeções
- Documentos
- Códigos QR
- Relatórios
- Usuários
- Configurações

Dashboard information should include useful high-level data such as:

- total de extintores;
- extintores operacionais;
- inspeções recentes;
- histórico de inspeções;
- próximas recargas;
- próximas manutenções;
- sincronizações pendentes;
- online/offline status.

Do not render actions the current user is not authorized to perform.

Responsive behavior must support desktop, tablet, and field/mobile use.

---

## 22. Service boundaries

Keep the following boundaries explicit:

| Boundary | Responsibility |
|---|---|
| Laravel | authentication, authorization, policies, validation, business rules, database, sync API, filesystem, transactions |
| Eloquent | persistence and relationships |
| Laravel Data | typed DTOs and application/data boundaries |
| Livewire | server-driven UI, forms, application state, user interactions |
| Blade | presentation, accessibility, pt-BR UI |
| Konva | blueprint visualization, markers, selection, zoom, pan, temporary overlap rendering |
| IndexedDB | offline field persistence, synchronized records, pending operations, offline media where practical |
| Service Worker | application shell/static caching and offline bootstrapping |
| `sync.js` | synchronization orchestration, queue processing, retries, online detection |
| Media Library | managed file/media storage and associations |
| Activity Log | audit/history |
| QR boundary | QR generation/preview/print orchestration |

Never:

- put database/business rules in JavaScript;
- put Konva internals into PHP;
- make Livewire manage the Konva scene graph;
- persist cluster coordinates;
- make Placement responsible for extinguisher-specific attributes;
- make Extinguisher responsible for blueprint coordinates.

---

## 23. Testing expectations

For every meaningful implementation change:

1. Identify the relevant specification requirements.
2. Implement the smallest appropriate change.
3. Add/update automated tests.
4. Run the relevant tests.
5. Fix failures before completion.

Important tests include:

### Domain/database

- one Extinguisher per Placement;
- nullable unassigned Extinguisher;
- UUID creation;
- duplicate serial prevention;
- transactional swaps;
- Placement identity preserved through swaps;
- physical coordinates preserved through swaps.

### Authorization

- authorized/unauthorized CRUD;
- field-level restrictions;
- authorized/unauthorized coordinate changes;
- authorized/unauthorized assignment and swap;
- synchronization authorization.

### Blueprint

- coordinate persistence;
- overlap detection;
- coordinates unchanged by visual clustering;
- zoom-dependent overlap calculation;
- reserve/empty Placement rendering;
- PDF validation and Media Library storage.

### Media

- blueprint PDF upload;
- inspection photo upload;
- extinguisher photo upload;
- invalid file rejection;
- size validation;
- Media Library association and authorization.

### Inspections

- valid/invalid inspection;
- authorization;
- inspection date;
- history;
- multiple inspections without overwrite;
- offline inspection synchronization.

### QR

- individual QR;
- stable Placement QR identity;
- QR identity after swap;
- batch generation;
- label data/template rendering;
- authorization.

### Batch

- template validation;
- unique UUIDs;
- duplicate serial prevention;
- reserve Placement assignment;
- one Extinguisher per Placement;
- job status/failure handling where jobs are used;
- authorization.

### Synchronization

- offline-created record;
- offline update;
- offline inspection;
- offline Placement update;
- duplicate retry;
- idempotency;
- unauthorized synchronization;
- invalid payload;
- stale record;
- conflict;
- server-authoritative state;
- acknowledgement;
- failed synchronization;
- assignment/swap synchronization;
- media synchronization where practical.

### Activity Log and Data

Verify important actions create expected Activity Log entries.

Test Laravel Data construction, validation, sync transformation, and authoritative response transformation.

### Browser/manual verification

Automated tests should be supplemented with documented browser/manual tests for:

- true offline behavior;
- service worker/PWA behavior;
- PDF rendering;
- browser printing;
- browser media capture;
- IndexedDB behavior;
- Konva interactions.

---

## 24. Seed/demo data

Development seeders should include representative data:

- multiple buildings;
- multiple floors;
- a non-confidential representative blueprint PDF;
- installed Placements;
- reserve Placements;
- empty reserve Placements;
- extinguisher types;
- brands/manufacturers;
- approximately 10–20 sample Extinguishers;
- inspection history;
- refill dates;
- maintenance dates;
- QR label templates;
- users for each role;
- representative media where practical.

Demonstrate:

- installed Placement -> Extinguisher;
- reserve Placement -> Extinguisher;
- reserve Placement -> no Extinguisher;
- multiple reserve Placements.

Use development-only accounts. Never hard-code production passwords or secrets.

---

## 25. Implementation phases

Implement incrementally and verify each major phase.

1. **Environment** — Codespaces, Dev Container, Docker Compose, MariaDB.
2. **Laravel Foundation** — Laravel, Livewire starter kit, authentication, Tailwind/Vite, Spatie packages, localization, Data classes.
3. **Core Domain** — buildings, floors, Placements, types, brands, Extinguishers, CRUD, dates, assignment, reserves, swaps, Activity Log.
4. **Media/Documents** — Media Library, PDFs, photos, documents, validation.
5. **Blueprint** — upload, PDF rendering, Konva, markers, editing mode, coordinates, overlap/cluster rendering.
6. **Inspections** — execution, items, results, notes, history, photos, approval.
7. **QR/Labels** — individual/Placement/Extinguisher QR, templates, preview, printing, batch QR.
8. **Batch Operations** — templates, reserve Placements, batch creation, validation, assignment, progress, audit.
9. **Offline Storage** — IndexedDB wrapper, hydration, queue, cached field data, offline inspections/media.
10. **Synchronization** — download/upload, operation UUIDs, idempotency, Data payloads, authorization, validation, conflicts, acknowledgements, authoritative state.
11. **PWA** — manifest, service worker, shell caching, connectivity state.
12. **Audit/Reporting** — Activity Log views, initial reports, synchronization history/filtering.
13. **Testing/Documentation** — feature/unit/sync/auth/media/activity/Data tests plus browser/manual verification and README updates.

After each major phase:

- run relevant tests;
- verify Laravel boots;
- verify MariaDB connectivity;
- verify authorization;
- verify affected UI;
- review the implementation against the specification;
- check architectural boundaries;
- fix regressions before continuing.

---

## 26. Documentation

Keep README documentation aligned with the implementation.

Document:

- project overview;
- architecture;
- requirements;
- Codespaces setup;
- local development;
- database configuration;
- authentication;
- roles/permissions;
- Placement architecture;
- reserve Placement architecture;
- Extinguisher/Placement relationship;
- swapping;
- blueprint architecture;
- physical-coordinate model;
- virtual-twin model;
- PDF handling;
- Media Library;
- Activity Log;
- Laravel Data;
- offline architecture;
- IndexedDB;
- synchronization protocol;
- conflict handling;
- inspection execution/history;
- QR generation;
- label templates;
- batch operations;
- testing;
- seed data;
- common commands;
- pt-BR localization;
- security assumptions;
- known limitations;
- future improvements.

Explicitly document these concepts:

```text
Placement
= authoritative physical managed position

Extinguisher
= physical equipment assigned to a Placement

One Placement
= zero or one Extinguisher

Reserve inventory
= individual reserve Placements

Physical coordinates
= authoritative real-world Placement position

Cluster/overlap offsets
= temporary renderer-derived values

Swap
= changing extinguisher assignment between existing Placements

Placement identity
= does not change during a swap

Inspection
= execution + results + photos + history

Inspection scheduling
= explicitly out of scope
```

---

## 27. Completion standard

Do not claim a feature is complete merely because code exists.

Before completion:

- verify the implementation against the relevant `BUILD_SPEC.md` requirements;
- run appropriate automated tests;
- inspect the resulting code for architectural violations;
- verify MariaDB compatibility;
- verify server-side authorization;
- verify pt-BR user-facing text;
- verify offline/sync behavior where applicable;
- verify Media Library and Activity Log usage where applicable;
- verify no unnecessary framework or dependency was introduced;
- report known limitations or unverified browser behavior.

The most important end-to-end scenario is:

1. Technician logs in online.
2. Technician opens an authorized building and floor.
3. Blueprint/PDF is displayed.
4. Placement virtual twins render at authoritative physical coordinates.
5. Overlaps are visually clustered without modifying coordinates.
6. Technician selects a Placement and its Extinguisher.
7. Technician opens an inspection.
8. Network connectivity is lost.
9. Previously synchronized authorized field data remains available.
10. Technician completes and saves the inspection in IndexedDB.
11. UI reports `Sincronização pendente`.
12. Connectivity returns.
13. Synchronization authenticates and re-authorizes with Laravel.
14. Laravel validates the operation and referenced records.
15. Inspection is written transactionally to MariaDB.
16. The client receives authoritative server state.
17. The pending operation becomes synchronized.
18. Failures/conflicts remain reviewable and do not silently overwrite server state.

---

## 28. Final architectural principle

**Keep the authoritative domain in Laravel + MariaDB, keep browser-only behavior isolated in JavaScript, and preserve the distinction between physical Placements, physical Extinguishers, and derived visual representations.**

Offline storage is a client-side capability, not a second database of record.

Synchronization is a server-authorized application protocol, not blind replication.

Activity Log is audit/history, Media Library is file/media management, Laravel Data is typed application-boundary data, and Spatie Permission is authorization.

When in doubt, choose the smallest Laravel-native implementation that preserves these boundaries.

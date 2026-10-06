# Panchved - Doctor Portal

Modern web portal for Panchved Ayurveda & Physiotherapy Rehab Center doctors.

## Modular Project Structure

The project has been organized into modular directories where each feature module is contained in its own folder:

```text
panchved/
├── api/                      # Backend PHP endpoints & database operations
├── assets/                   # Shared image assets, brand logos & icons
├── appointments/             # Appointments & Prescriptions module
│   ├── appointments.html     # Upcoming & past appointments listing
│   ├── appointments.css      # Appointments styles
│   ├── add-prescription.html # Write prescription & diagnosis form
│   └── add-prescription.css  # Prescription page styles
├── courses/                  # Workshops & Courses module
│   ├── courses.html          # Workshops directory & booking
│   ├── courses.css           # Workshops styles
│   └── course-details.html   # Single workshop details & checkout
├── dashboard/                # Dashboard module
│   └── dashboard.html        # Doctor metrics & consultation overview
├── packages/                 # Treatment Packages module
│   ├── packages.html         # Treatment packages list
│   ├── packages.css          # Packages styles
│   ├── packages.js           # Dynamic packages fetching
│   └── protocol-details.html # Package clinical protocols
├── patients/                 # Patients module
│   ├── patients.html         # Patients list & status management
│   ├── patients.css          # Patients directory styles
│   ├── patients.js           # Patients API integration
│   ├── patient-details.html  # Comprehensive patient record & history
│   ├── patient-details.css   # Patient profile styles
│   └── patient-details.js    # Patient details actions & history
├── profile/                  # Doctor Profile module
│   ├── profile.html          # Doctor profile view & edit details
│   └── profile.css           # Profile styles
├── shared/                   # Shared styles, scripts & configurations
│   ├── api_config.js         # API path resolution & session helpers
│   ├── dashboard.css         # Common layout, sidebar & UI components
│   └── dashboard.js          # Shared sidebar, header sync & toast utilities
├── index.html                # Doctor portal login entrypoint
├── style.css                 # Login page styles
├── script.js                 # Login form & authentication handler
├── schema.sql                # MySQL Database schema & seed data
└── README.md                 # Project documentation
```
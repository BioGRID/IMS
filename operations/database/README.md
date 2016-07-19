# Database Migration
Utilities for migrating an instance of BioGRID IMS 2.0 database to the latest database structure. Requires configuration variables set in <base>/config/config.json to operate correctly.

## Build Process

### Core Only
- **Run: python Build.py -v -c -o core** - This will clean the core tables and prepare them for rebuilding
- **Run: python Build.py -v -b -o core** - This will build the core tables
- **Run: php LoadColumnDefinitions.php** - This will populate the column definitions columns for interaction_types
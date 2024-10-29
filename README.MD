# Public Google Drive Scanner and Database Builder

## Project Overview

This project is a Laravel-based application created to scan a public Google Drive, capturing the folder structure and extracting file metadata. Attributes for each file are derived from both the folder hierarchy and the file names, creating a rich dataset for enhanced search and filtering.

The structured data is stored in a database, and MeiliSearch powers an advanced search functionality. This setup allows users to easily navigate through extensive file structures and efficiently locate files based on custom attributes.

## Key Functionalities

- **Attribute Extraction**: Generates searchable attributes based on the folder structure and file naming conventions, enhancing the ability to filter and categorize files.
- **Database Storage**: Organizes extracted data into a structured database for efficient management and retrieval.
- **Advanced Search & Filtering**: Leverages MeiliSearch to provide fast, real-time search and filter capabilities, enabling users to locate files by custom attributes derived from folder and file names.

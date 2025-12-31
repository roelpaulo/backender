# Changelog

All notable changes to Backender will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Real-world testing and bug fixes
- Performance optimization
- Additional security hardening

## [0.1.0] - 2026-01-01

### Status
🚧 **Initial Development** - Not yet tested in production environments

### Added
- Custom API endpoint creation via web UI
- Monaco Editor (VS Code) for PHP code editing
- Email-based authentication with verification
- Password complexity requirements and validation
- Password reset functionality via email
- API key authentication system
- Per-endpoint authentication toggle (public vs protected)
- SQLite database with PDO access
- Dark theme interface (TailwindCSS + DaisyUI)
- Request and error logging
- Enable/disable endpoints
- CORS support for JavaScript frontends
- Mobile-responsive design
- Docker containerization with nginx + PHP-FPM
- Persistent storage for database, endpoints, and logs
- Support for GET, POST, PUT, DELETE, PATCH methods
- JSON and text response handling
- Query parameter and POST data access
- External API integration support
- Documentation (Quick Start, Authentication, API Keys, HTTP Methods, Examples, Architecture)

### Security
- Bcrypt password hashing
- Email verification required before login
- Secure token-based password reset
- API key authentication with X-API-Key header
- Session-based admin authentication
- Prepared SQL statements (no SQL injection)
- No eval() or dynamic includes

[Unreleased]: https://github.com/roelpaulo/backender/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/roelpaulo/backender/releases/tag/v0.1.0

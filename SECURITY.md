# Security Policy

> This project follows the [Zairakai Global Security Policy][handbook-security].
> Please refer to it for standard protections, response timeline, and contact information.

---

## 🔒 Reporting Vulnerabilities

| Channel | Description | Contact / Link |
| :--- | :--- | :--- |
| **GitLab Issues** | For non-sensitive issues (bugs, public vulnerabilities). | [Open Issue][issues] |
| **Service Desk** | Preferred channel for sensitive reports. | `contact-project+zairakai-php-packages-laravel-eloquent-80184988-issue-@incoming.gitlab.com` |
| **Email** | Alternative secure contact. | `security@the-white-rabbits.fr` |

Please **do not disclose vulnerabilities publicly** until they have been reviewed.

---

## 🛡️ Security Features

### Protection Layers

| Layer | Security Protection |
| :--- | :--- |
| **Static Analysis** | PHPStan Level Max compliance and Rector modernizations. |
| **CI Pipeline** | Automated secret detection in GitLab CI. |

---

## 🔍 Security Scope

`zairakai/laravel-eloquent` provides Eloquent base classes, column resolution helpers, and model conversion tooling:

- no external network calls
- no dynamic code execution (`eval`, `exec`, shell calls)
- `eloquent:convert` only reads and writes files within the provided local path

You remain responsible for output escaping, authorization, and controlling which attributes
are exposed through serialization (use `$hidden` as needed).

---

[handbook-security]: https://gitlab.com/zairakai/handbook/-/blob/main/SECURITY.md
[issues]: https://gitlab.com/zairakai/php-packages/laravel-eloquent/-/issues

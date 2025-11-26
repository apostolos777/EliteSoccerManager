iupload
======

Simple uploader for deployments. Places uploads in `uploads/iupload`.

Notes:
- Intended for internal, low-risk environments. Review and harden before public use.
- Allowed file types: jpg, jpeg, png, gif, pdf, csv, txt
- Max file size: 10 MB

To use:
- Visit `/iupload/` on your deployed site (e.g., https://example.com/iupload/)
- Upload files using the form

Security:
- This is intentionally minimal. Consider adding authentication, CSRF protection, virus scanning, and stricter file validation before exposing publicly.

# PartOps v4 - Prototype Files

The original HTML prototypes have been preserved with `_prototype-` prefix:

- `_prototype-index.html` - Original full prototype with all forms
- `_prototype-part-detail.html` - Desktop part detail prototype
- `_prototype-qr-scanner.html` - Mobile QR scanner prototype

These files are kept for reference but are not served by the web server.

## Viewing Prototypes

To view the prototypes locally:

```bash
# Open in browser
open _prototype-index.html
# or
firefox _prototype-index.html
```

## Live Application

The live PHP application is now served from the `public/` directory:
- **URL:** https://php.sbastola.com/partops/v4/
- **Entry Point:** `public/index.php`
- **Routes:** Defined in `routes/web.php`

## Migration Notes

The prototypes were used as UI reference during development. All functionality has been implemented in the PHP MVC application with the following improvements:

- Dynamic data from MySQL database
- Server-side validation
- Error logging system
- Session management
- CSRF protection (to be implemented)
- Real-time inventory tracking

The prototype UI patterns and Tailwind CSS styling have been preserved in the view templates.

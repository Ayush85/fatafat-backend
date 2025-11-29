<!DOCTYPE html>
<html>
  <head>
    <title>Fatafat API Documentation</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui.min.css">
    <style>
      html{
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
      }
      *,
      *:before,
      *:after{
        box-sizing: inherit;
      }
      body {
        margin:0;
        background: #fafafa;
        font-family: 'Roboto', sans-serif;
      }
      .topbar {
        background-color: #1a1a1a;
        padding: 10px 0;
      }
      .topbar-wrapper {
        max-width: 1460px;
        margin: 0 auto;
        padding: 0 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .topbar a {
        display: inline-block;
        color: white;
        text-decoration: none;
        font-size: 18px;
        font-weight: 600;
      }
      .topbar a:hover {
        color: #ffc107;
      }
      .info-section {
        background: white;
        padding: 20px;
        max-width: 1460px;
        margin: 20px auto;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
      }
      .info-section h2 {
        margin-top: 0;
        color: #333;
      }
      .auth-info {
        background: #e3f2fd;
        padding: 15px;
        border-left: 4px solid #2196f3;
        margin: 10px 0;
        border-radius: 3px;
      }
      .auth-info strong {
        color: #1565c0;
      }
    </style>
  </head>
  <body>
    <div class="topbar">
      <div class="topbar-wrapper">
        <a href="#">Fatafat E-Commerce API Documentation</a>
      </div>
    </div>

    <div class="info-section">
      <h2>Welcome to Fatafat API</h2>
      <p>Complete API documentation for the Fatafat E-Commerce platform. Use this interface to explore and test all available endpoints.</p>
      
      <h3>API Authentication</h3>
      <div class="auth-info">
        <strong>Bearer Token:</strong> Used for Admin and User authenticated endpoints. Include the token in the Authorization header as: <code>Bearer YOUR_TOKEN</code>
      </div>
      <div class="auth-info">
        <strong>API Key:</strong> Used for Public API endpoints. Include the key in the X-API-Key header.
      </div>

      <h3>Quick Links</h3>
      <ul>
        <li><strong>Admin API:</strong> Manage categories, brands, products, vendors, users, etc.</li>
        <li><strong>Public API:</strong> Browse products, categories, blogs without authentication</li>
        <li><strong>User API:</strong> Register, login, manage cart and orders</li>
      </ul>

      <h3>Base URL</h3>
      <p><code>http://localhost:8000/api</code> (Development)</p>
      <p><code>https://api.fatafat.com/api</code> (Production)</p>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-bundle.min.js"></script>
    <script>
      window.onload = function() {
        const ui = SwaggerUIBundle({
          url: "/openapi.json",
          dom_id: '#swagger-ui',
          deepLinking: true,
          presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIBundle.SwaggerUIStandalonePreset
          ],
          plugins: [
            SwaggerUIBundle.plugins.DownloadUrl
          ],
          layout: "BaseLayout",
          defaultModelsExpandDepth: 1,
          defaultModelExpandDepth: 1,
          requestInterceptor: (request) => {
            // Add custom headers if needed
            return request;
          }
        });
        window.ui = ui;
      }
    </script>
  </body>
</html>

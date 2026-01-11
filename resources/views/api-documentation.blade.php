<!DOCTYPE html>
<html>

<head>
  <title>Fatafat Sewa API Documentation</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui.min.css">
  <style>
    :root {
      --primary-blue: #2B6CB0;
      --primary-orange: #FF8C00;
      --dark-blue: #1e4d7b;
      --light-blue: #e6f2ff;
    }

    html {
      box-sizing: border-box;
      overflow: -moz-scrollbars-vertical;
      overflow-y: scroll;
    }

    *,
    *:before,
    *:after {
      box-sizing: inherit;
    }

    body {
      margin: 0;
      background: #fafafa;
      font-family: 'Roboto', sans-serif;
    }

    /* Custom Swagger UI Branding */
    .swagger-ui .topbar {
      background-color: var(--primary-blue) !important;
      padding: 15px 0;
      border-bottom: 3px solid var(--primary-orange);
    }

    .swagger-ui .topbar .topbar-wrapper {
      max-width: 1460px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .swagger-ui .topbar .topbar-wrapper a {
      color: white !important;
      font-size: 20px;
      font-weight: 600;
    }

    .swagger-ui .info {
      margin: 30px 0;
    }

    .swagger-ui .info .title {
      color: var(--primary-blue) !important;
      font-size: 36px;
    }

    /* Operation colors */
    .swagger-ui .opblock.opblock-get {
      border-color: var(--primary-blue);
      background: rgba(43, 108, 176, 0.1);
    }

    .swagger-ui .opblock.opblock-get .opblock-summary-method {
      background: var(--primary-blue);
    }

    .swagger-ui .opblock.opblock-post {
      border-color: var(--primary-orange);
      background: rgba(255, 140, 0, 0.1);
    }

    .swagger-ui .opblock.opblock-post .opblock-summary-method {
      background: var(--primary-orange);
    }

    .swagger-ui .opblock.opblock-put {
      border-color: #f59e0b;
      background: rgba(245, 158, 11, 0.1);
    }

    .swagger-ui .opblock.opblock-delete {
      border-color: #dc2626;
      background: rgba(220, 38, 38, 0.1);
    }

    /* Buttons */
    .swagger-ui .btn.execute {
      background-color: var(--primary-orange);
      border-color: var(--primary-orange);
      color: white;
    }

    .swagger-ui .btn.execute:hover {
      background-color: #e67e00;
      border-color: #e67e00;
    }

    .swagger-ui .btn.authorize {
      background-color: var(--primary-blue);
      border-color: var(--primary-blue);
      color: white;
    }

    .swagger-ui .btn.authorize:hover {
      background-color: var(--dark-blue);
      border-color: var(--dark-blue);
    }

    /* Links */
    .swagger-ui a {
      color: var(--primary-blue);
    }

    .swagger-ui a:hover {
      color: var(--primary-orange);
    }

    /* Info section styling */
    .info-header {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
      color: white;
      padding: 40px 20px;
      margin: -20px -20px 30px -20px;
      border-radius: 8px 8px 0 0;
    }

    .info-header h1 {
      margin: 0 0 10px 0;
      font-size: 32px;
      font-weight: 700;
    }

    .info-header p {
      margin: 0;
      font-size: 16px;
      opacity: 0.9;
    }

    .info-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin: 20px 0;
    }

    .info-card {
      background: white;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      padding: 20px;
      transition: all 0.3s ease;
    }

    .info-card:hover {
      border-color: var(--primary-blue);
      box-shadow: 0 4px 12px rgba(43, 108, 176, 0.15);
      transform: translateY(-2px);
    }

    .info-card h3 {
      color: var(--primary-blue);
      margin: 0 0 10px 0;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-card .icon {
      width: 24px;
      height: 24px;
      background: var(--primary-orange);
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 14px;
    }

    .info-card p {
      margin: 0;
      color: #6b7280;
      line-height: 1.6;
    }

    .info-card code {
      background: var(--light-blue);
      color: var(--primary-blue);
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 13px;
    }

    .auth-section {
      background: var(--light-blue);
      border-left: 4px solid var(--primary-blue);
      padding: 20px;
      border-radius: 4px;
      margin: 20px 0;
    }

    .auth-section h3 {
      color: var(--primary-blue);
      margin: 0 0 15px 0;
    }

    .auth-method {
      background: white;
      padding: 15px;
      border-radius: 4px;
      margin: 10px 0;
      border: 1px solid #d1d5db;
    }

    .auth-method strong {
      color: var(--primary-orange);
      display: block;
      margin-bottom: 5px;
    }

    .feature-list {
      list-style: none;
      padding: 0;
    }

    .feature-list li {
      padding: 8px 0;
      padding-left: 24px;
      position: relative;
    }

    .feature-list li:before {
      content: "✓";
      position: absolute;
      left: 0;
      color: var(--primary-orange);
      font-weight: bold;
    }

    /* Try it out section */
    .swagger-ui .try-out__btn {
      background: var(--primary-blue);
      border-color: var(--primary-blue);
      color: white;
    }

    .swagger-ui .try-out__btn:hover {
      background: var(--dark-blue);
      border-color: var(--dark-blue);
    }

    /* Response section */
    .swagger-ui .responses-inner h4,
    .swagger-ui .responses-inner h5 {
      color: var(--primary-blue);
    }

    /* Schema */
    .swagger-ui .model-box {
      background: var(--light-blue);
    }

    .swagger-ui .model-title {
      color: var(--primary-blue);
    }
  </style>
</head>

<body>
  <div id="swagger-ui"></div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-standalone-preset.min.js"></script>
  <script>
    window.onload = function () {
      const ui = SwaggerUIBundle({
        url: "/documentation/openapi.yaml",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout",
        defaultModelsExpandDepth: 1,
        defaultModelExpandDepth: 1,
        docExpansion: "list",
        filter: true,
        tryItOutEnabled: true,
        persistAuthorization: true,
        requestInterceptor: (request) => {
          // Automatically add API-Key header if set in authorization
          return request;
        },
        onComplete: () => {
          // Add custom info section
          const infoContainer = document.querySelector('.information-container');
          if (infoContainer) {
            const customInfo = `
              <div class="info-header">
                <h1>Fatafat Sewa API Documentation</h1>
                <p>Complete REST API for E-Commerce Platform - Test all endpoints interactively</p>
              </div>

              <div class="auth-section">
                <h3>Authentication Methods</h3>
                <div class="auth-method">
                  <strong>API Key Authentication</strong>
                  <p>For public endpoints, include: <code>API-Key: your-api-key-here</code></p>
                  <p>Click the "Authorize" button above to set your API key for all requests.</p>
                  <p>Test API Key: <code>test-key-123</code></p>
                </div>
                <div class="auth-method">
                  <strong>Bearer Token Authentication</strong>
                  <p>For authenticated endpoints, include: <code>Authorization: Bearer your-token</code></p>
                  <p>Obtain token via <code>/api/v1/login</code> endpoint.</p>
                </div>
              </div>

              <div class="info-cards">
                <div class="info-card">
                  <h3><span class="icon">API</span> Base URLs</h3>
                  <p><strong>Development:</strong><br><code>http://localhost:8002/api</code></p>
                  <p><strong>Production:</strong><br><code>https://api.fatafatsewa.com/api</code></p>
                </div>

                <div class="info-card">
                  <h3>📦 Media Configuration</h3>
                  <p>All media files (images, documents) are served from the main domain:</p>
                  <code>https://fatafatsewa.com/storage/</code>
                  <p style="margin-top: 10px;">Product images include:</p>
                  <ul style="margin-left: 20px;">
                    <li><strong>full</strong>: Original image</li>
                    <li><strong>thumb</strong>: Thumbnail (200x200, WebP)</li>
                    <li><strong>preview</strong>: Preview size (400x400, WebP)</li>
                  </ul>
                </div>

                <div class="info-card">
                  <h3>⚡ Performance Optimization</h3>
                  <p>By default, product endpoints return minimal data (media only) for faster responses.</p>
                  <p style="margin-top: 10px;"><strong>To load additional relationships, use the <code>include</code> parameter:</strong></p>
                  <code>?include=brand,categories,vendor,variants</code>
                  
                  <p style="margin-top: 15px;"><strong>Examples:</strong></p>
                  <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; margin-top: 5px;">
# Fast - Media only (default)
GET /api/v1/products?per_page=10

# With brand and categories
GET /api/v1/products?per_page=10&include=brand,categories

# Full data with all relationships
GET /api/v1/products?per_page=10&include=brand,categories,vendor,variants</pre>
                  
                  <p style="margin-top: 10px;"><strong>Available relationships:</strong></p>
                  <ul style="margin-left: 20px;">
                    <li><code>brand</code> - Product brand information</li>
                    <li><code>categories</code> - Product categories</li>
                    <li><code>vendor</code> - Vendor/seller information</li>
                    <li><code>variants</code> - Product variants with images</li>
                  </ul>
                </div>

                <div class="info-card">
                  <h3><span class="icon">TEST</span> Testing Features</h3>
                  <ul class="feature-list">
                    <li>Try out all endpoints directly</li>
                    <li>View request/response examples</li>
                    <li>Download OpenAPI spec</li>
                    <li>Export Postman collection</li>
                  </ul>
                </div>
              </div>
            `;
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = customInfo;
            infoContainer.insertBefore(tempDiv, infoContainer.firstChild);
          }
        }
      });
      window.ui = ui;
    }
  </script>
</body>

</html>
Usage Guide
===========

.. _installation:

Installation
------------

Prerequisites
~~~~~~~~~~~~~

- PHP 8.3 or higher
- Composer
- Node.js & npm
- MySQL/PostgreSQL/SQLite

Setup
~~~~~

To set up DevHub locally, follow these steps:

.. code-block:: console

   $ git clone https://github.com/your-org/devhub.git
   $ cd devhub
   $ composer install
   $ npm install
   $ cp .env.example .env
   $ php artisan key:generate
   $ php artisan jwt:secret
   $ php artisan migrate
   $ npm run build
   $ php artisan serve

Your API will be available at ``http://localhost:8000/api/v1``

Configuration
-------------

Environment Variables
~~~~~~~~~~~~~~~~~~~~~

Update your ``.env`` file with the following configurations:

.. code-block:: text

   # Application
   APP_NAME=DevHub
   APP_URL=http://localhost:8000

   # Database
   DB_CONNECTION=mysql
   DB_DATABASE=devhub

   # JWT Authentication
   JWT_SECRET=your-secret-key
   JWT_TTL=60

   # Search (Algolia)
   ALGOLIA_APP_ID=your-app-id
   ALGOLIA_SECRET=your-secret

   # Storage (Cloudinary)
   CLOUDINARY_URL=cloudinary://...

   # Code Execution (Piston)
   PISTON_API_URL=https://emkc.org/api/v2/piston
   PISTON_API_KEY=your-api-key

   # AI Services
   LLAMA_API_URL=your-llama-url
   LLAMA_KEY=your-llama-key

Authentication
--------------

DevHub uses JWT (JSON Web Token) authentication. Include the token in the Authorization header:

.. code-block:: http

   Authorization: Bearer <your_jwt_token>

Login Example
~~~~~~~~~~~~~

.. code-block:: console

   $ curl -X POST http://localhost:8000/api/v1/login \
     -H "Content-Type: application/json" \
     -d '{"email": "user@example.com", "password": "password"}'

Response:

.. code-block:: json

   {
     "success": true,
     "message": "Login successful",
     "data": {
       "user": {"id": 1, "name": "John Doe"},
       "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
       "token_type": "bearer",
       "expires_in": 3600
     }
   }

Rate Limiting
~~~~~~~~~~~~~

API requests are rate-limited to prevent abuse:

+----------------------+---------------------+
| Endpoint Type        | Limit               |
+======================+=====================+
| Public endpoints     | 15 requests/minute  |
+----------------------+---------------------+
| Protected endpoints  | 25 requests/minute  |
+----------------------+---------------------+

Docker Deployment
-----------------

Using Docker
~~~~~~~~~~~~

.. code-block:: console

   $ docker build -t devhub .
   $ docker run -p 8000:8000 devhub

Using Deploy Script
~~~~~~~~~~~~~~~~~~~

.. code-block:: console

   $ ./deploy.sh

Testing
-------

Run all tests:

.. code-block:: console

   $ php artisan test

Run with coverage:

.. code-block:: console

   $ php artisan test --coverage

Run specific test:

.. code-block:: console

   $ php artisan test tests/Feature/PostTest.php

Debugging
---------

**Laravel Telescope**
   Access at ``/telescope`` for request monitoring, queries, jobs, and more.

**Log Viewer**
   Access at ``/log-viewer`` for application logs.

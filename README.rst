DevHub Community - Developer Platform
=====================================

.. image:: https://img.shields.io/badge/Laravel-12-red.svg
    :alt: Laravel 12

.. image:: https://img.shields.io/badge/PHP-8.3+-blue.svg
    :alt: PHP 8.3+

.. image:: https://img.shields.io/badge/License-MIT-green.svg
    :alt: License MIT

.. image:: https://img.shields.io/badge/API-REST-orange.svg
    :alt: REST API

.. image:: https://img.shields.io/badge/Status-Production-brightgreen.svg
    :alt: Production

A modern, feature-rich social platform built for developers to share knowledge, collaborate, and grow together. Built with Laravel 12 and cutting-edge web technologies.

| **API Documentation:** https://0yviq6a5i5.apidog.io
| **Production URL:** https://devhub.eu-north-1.elasticbeanstalk.com
| **API Base URL:** https://devhub.eu-north-1.elasticbeanstalk.com/api/v1

.. contents:: Contents
   :depth: 3
   :local:

----

Features
========

Authentication & Users
----------------------
- JWT-based secure authentication with refresh tokens
- Social login (Google, GitHub, Microsoft, Medium)
- Email verification with OTP
- Password reset with OTP verification
- Profile customization with avatars & cover images
- User status & availability indicators
- Alternative email support

Content & Social
----------------
- Rich post creation with tags & categories
- Nested comment threads with reactions
- Reading lists with notes & organization
- Follow system for users & tags
- Real-time notifications
- Post views tracking
- Saved posts/bookmarks

AI-Powered Tools
----------------
- Built-in code editor with 50+ language support
- AI chat assistant (LLama integration)
- Post summarization & translation
- Content analysis & generation
- Question answering about posts

Moderation & Security
---------------------
- Content reporting system
- User blocking functionality
- Rate limiting & throttling
- Soft & hard delete options

----

Tech Stack
==========

+------------------+----------------------------------------+
| Component        | Technology                             |
+==================+========================================+
| **Backend**      | Laravel 12, PHP 8.3+                   |
+------------------+----------------------------------------+
| **Auth**         | JWT (tymon/jwt-auth)                   |
+------------------+----------------------------------------+
| **Search**       | Algolia, Laravel Scout                 |
+------------------+----------------------------------------+
| **Storage**      | AWS S3, Cloudinary                     |
+------------------+----------------------------------------+
| **Code Exec**    | Piston API                             |
+------------------+----------------------------------------+
| **AI**           | LLama, Custom Models                   |
+------------------+----------------------------------------+
| **Monitoring**   | Laravel Telescope, Log Viewer          |
+------------------+----------------------------------------+
| **Deployment**   | AWS Elastic Beanstalk, Docker          |
+------------------+----------------------------------------+

----

Quick Start
===========

Prerequisites
-------------
- PHP 8.3 or higher
- Composer
- Node.js & npm
- MySQL/PostgreSQL/SQLite

Installation
------------

.. code-block:: bash

    # Clone the repository
    git clone https://github.com/your-org/devhub.git
    cd devhub

    # Install dependencies
    composer install
    npm install

    # Environment setup
    cp .env.example .env
    php artisan key:generate
    php artisan jwt:secret

    # Database setup
    php artisan migrate
    php artisan db:seed

    # Build assets & start server
    npm run build
    php artisan serve

Your API will be available at ``http://localhost:8000/api/v1``

----

Configuration
=============

Create a ``.env`` file with the following key configurations:

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

----

.. _api-reference:

API Reference
=============

Base URL
--------

.. code-block:: text

    http://devhub.eu-north-1.elasticbeanstalk.com/api/v1

Authentication Header
---------------------

All protected endpoints require JWT token:

.. code-block:: text

    Authorization: Bearer <your-jwt-token>

Rate Limits
-----------
- **Public endpoints:** 15 requests/minute
- **Protected endpoints:** 25 requests/minute

----

.. _authentication-apis:

Authentication APIs
===================

Public Endpoints (No Authentication Required)
----------------------------------------------

+----------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+========================================+========+==================================+
| ``/api/v1/login``                      | POST   | User login                       |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/register``                   | POST   | User registration                |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/auth/google/login``          | POST   | Login with Google OAuth          |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/auth/google/callback``       | GET    | Google OAuth callback            |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/auth/github/login``          | POST   | Login with GitHub OAuth          |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/auth/github/callback``       | GET    | GitHub OAuth callback            |
+----------------------------------------+--------+----------------------------------+

**POST /api/v1/login - User Login**

Request:

.. code-block:: json

    {
        "email": "user@example.com",
        "password": "your_password"
    }

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Login successful",
        "data": {
            "user": {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "email": "user@example.com",
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
                "email_verified_at": "2026-01-15T10:00:00Z"
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "token_type": "bearer",
            "expires_in": 3600
        }
    }

Error Error Response (401 Unauthorized):

.. code-block:: json

    {
        "success": false,
        "message": "Invalid credentials"
    }

**POST /api/v1/register - User Registration**

Request:

.. code-block:: json

    {
        "name": "John Doe",
        "username": "johndoe",
        "email": "john@example.com",
        "password": "SecurePass123!",
        "password_confirmation": "SecurePass123!"
    }

Success Success Response (201 Created):

.. code-block:: json

    {
        "success": true,
        "message": "Registration successful. Please verify your email.",
        "data": {
            "user": {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "email": "john@example.com"
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
        }
    }

Error Error Response (422 Validation Error):

.. code-block:: json

    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "email": ["The email has already been taken."],
            "username": ["The username has already been taken."]
        }
    }

Email Verification
------------------

+----------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+========================================+========+==================================+
| ``/api/v1/email/send-otp``             | POST   | Send verification OTP            |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/email/verify-otp``           | POST   | Verify email with OTP            |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/email/is-verified``          | GET    | Check verification status        |
+----------------------------------------+--------+----------------------------------+

**POST /api/v1/email/send-otp - Send Verification OTP**

Request:

.. code-block:: json

    {
        "email": "user@example.com"
    }

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "OTP sent successfully to your email"
    }

**POST /api/v1/email/verify-otp - Verify Email with OTP**

Request:

.. code-block:: json

    {
        "email": "user@example.com",
        "otp": "123456"
    }

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Email verified successfully"
    }

**GET /api/v1/email/is-verified - Check Verification Status**

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "data": {
            "is_verified": true,
            "verified_at": "2026-02-17T10:30:00Z"
        }
    }

Password Recovery
-----------------

+----------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+========================================+========+==================================+
| ``/api/v1/password/forgot``            | POST   | Request password reset           |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/password/verify-otp``        | POST   | Verify reset OTP                 |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/password/reset``             | POST   | Set new password                 |
+----------------------------------------+--------+----------------------------------+

**POST /api/v1/password/forgot - Request Password Reset**

Request:

.. code-block:: json

    {
        "email": "user@example.com"
    }

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Password reset OTP sent to your email"
    }

**POST /api/v1/password/verify-otp - Verify Reset OTP**

Request:

.. code-block:: json

    {
        "email": "user@example.com",
        "otp": "123456"
    }

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "OTP verified successfully",
        "data": {
            "reset_token": "temp-reset-token-xyz"
        }
    }

**POST /api/v1/password/reset - Set New Password**

Request:

.. code-block:: json

    {
        "email": "user@example.com",
        "otp": "123456",
        "password": "NewSecurePass123!",
        "password_confirmation": "NewSecurePass123!"
    }

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Password reset successfully"
    }

Protected Authentication Endpoints
----------------------------------

+----------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+========================================+========+==================================+
| ``/api/v1/logout``                     | POST   | Logout current user              |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/refresh``                    | POST   | Refresh JWT access token         |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/me``                         | GET    | Get current user profile         |
+----------------------------------------+--------+----------------------------------+

**POST /api/v1/logout - Logout Current User**

Headers:

.. code-block:: http

    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Successfully logged out"
    }

**POST /api/v1/refresh - Refresh JWT Access Token**

Headers:

.. code-block:: http

    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "data": {
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "token_type": "bearer",
            "expires_in": 3600
        }
    }

**GET /api/v1/me - Get Current User Profile**

Headers:

.. code-block:: http

    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

Success Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "data": {
            "id": 1,
            "name": "John Doe",
            "username": "johndoe",
            "email": "user@example.com",
            "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
            "cover_image": "https://res.cloudinary.com/devhub/image/cover.jpg",
            "bio": "Full-stack developer",
            "location": "San Francisco, CA",
            "website": "https://johndoe.dev",
            "github": "johndoe",
            "linkedin": "johndoe",
            "email_verified_at": "2026-01-15T10:00:00Z",
            "created_at": "2026-01-01T00:00:00Z"
        }
    }

----

.. _posts-apis:

Posts APIs
==========

CRUD Operations
---------------

+----------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+========================================+========+==================================+
| ``/api/v1/posts``                      | GET    | List all posts (paginated)       |
+----------------------------------------+--------+----------------------------------+
| ``/api/v1/posts``                       | POST   | Create new post                  |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}``                  | GET    | Get single post                  |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}``                  | PUT    | Update post                      |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}``                  | PATCH  | Partial update post              |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}``                  | DELETE | Soft delete post                 |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/force``            | DELETE | Permanently delete post          |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/restore``          | POST   | Restore deleted post             |
+----------------------------------+--------+----------------------------------+

Post Queries
------------

+----------------------------------+--------+----------------------------------+
| URL                         | Method | Description                      |
+==================================+========+==================================+
| ``/api/v1/user/posts``                  | GET    | Get current user's posts         |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/recent``                | GET    | Get recent posts                 |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/top-views``             | GET    | Get top viewed posts             |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/drafts``                | GET    | Get user's draft posts           |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/archives``              | GET    | Get archived/trashed posts       |
+----------------------------------+--------+----------------------------------+

Post Tags & Comments
--------------------

+----------------------------------+--------+----------------------------------+
| URL                         | Method | Description                      |
+==================================+========+==================================+
| ``/api/v1/posts/{id}/tags``             | GET    | Get post tags (detailed)         |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/tags-list``        | GET    | Get post tags (list)             |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/comments``         | GET    | Get post comments                |
+----------------------------------+--------+----------------------------------+

Post Reporting
--------------

+----------------------------------+--------+----------------------------------+
| URL                         | Method | Description                      |
+==================================+========+==================================+
| ``/api/v1/posts/{id}/report``           | POST   | Report a post                    |
+----------------------------------+--------+----------------------------------+
| ``/posts/report/reasons``        | GET    | Get report reasons               |
+----------------------------------+--------+----------------------------------+

Post Views
----------

+----------------------------------+--------+----------------------------------+
| URL                         | Method | Description                      |
+==================================+========+==================================+
| ``/api/v1/posts/viewed/recent``         | GET    | Get recently viewed posts        |
+----------------------------------+--------+----------------------------------+
| ``/api/v1/posts/viewed/clear``          | DELETE | Clear viewed posts history       |
+----------------------------------+--------+----------------------------------+

**Create Post Request:**

.. code-block:: json

    {
        "title": "Getting Started with Laravel",
        "content": "Laravel is a powerful PHP framework...",
        "status": "published",
        "tags": ["laravel", "php", "tutorial"]
    }

**GET /api/v1/posts - List All Posts**

Query Parameters: ``?page=1&per_page=15``

Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "data": [
----

.. _comments-apis:

Comments APIs
=============

Create & Reply
--------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/posts/{postId}/comments``                | POST   | Create comment on post           |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{postId}/comments/{commentId}/reply`` | POST | Reply to a comment               |
+---------------------------------------------+--------+----------------------------------+

Get Comments
------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/posts/{postId}/comments``                | GET    | Get comments for a post          |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{postId}/comments/count``          | GET    | Get comment count for post       |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{userId}/comments``                | GET    | Get comments by user             |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/comments/{id}/replies``                  | GET    | Get replies to a comment         |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/comments/{id}/thread``                   | GET    | Get full comment thread          |
+---------------------------------------------+--------+----------------------------------+

Comment Actions
---------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/comments/{id}/pin``                      | POST   | Pin a comment                    |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/comments/{id}/unpin``                    | POST   | Unpin a comment                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/comments/{id}/force``                    | DELETE | Delete comment permanently       |
+---------------------------------------------+--------+----------------------------------+

Comment Reactions
-----------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/comments/{id}/react``                    | POST   | React to a comment               |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/comments/{id}/remove-react``             | DELETE | Remove reaction from comment     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/comments/{id}/my-reaction``              | GET    | Get my reaction on comment       |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/comments/{id}/reactions``                | GET    | Get all reactions on comment     |
+---------------------------------------------+--------+----------------------------------+

My Comments
-----------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/my/comments``                            | GET    | Get my recent comments           |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/my/comments/stats``                      | GET    | Get my comment statistics        |
+---------------------------------------------+--------+----------------------------------+

----

Reactions APIs
=================

Post Reactions
--------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/posts/{id}/react``                       | POST   | React to a post                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/remove-react``                | DELETE | Remove reaction from post        |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/my-reaction``                 | GET    | Get my reaction on post          |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/reactions-count``             | GET    | Get reaction counts              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{id}/reactors``                    | GET    | Get list of reactors             |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/user/posts/total-reactions``             | GET    | Get total reactions on my posts  |
+---------------------------------------------+--------+----------------------------------+

**React Request:**

.. code-block:: json

    {
        "reaction": "like"
    }

**Available Reactions:** ``like``, ``love``, ``clap``, ``insightful``, ``celebrate``

----

Users APIs
==========

User Discovery
--------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/users``                                  | GET    | List all users                   |
+---------------------------------------------+--------+----------------------------------+
| ``/users/recommended``                      | GET    | Get recommended users            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}``                             | GET    | Get user profile                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/similar-skills``              | GET    | Get users with similar skills    |
+---------------------------------------------+--------+----------------------------------+

User Content
------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/users/{id}/posts``                       | GET    | Get user's posts                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/comments``                    | GET    | Get user's comments              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/tags``                        | GET    | Get user's followed tags         |
+---------------------------------------------+--------+----------------------------------+

User Followers
--------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/users/{id}/followers``                   | GET    | Get user's followers             |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/followers/count``             | GET    | Get followers count              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/following``                   | GET    | Get users being followed         |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/mutual-followers``            | GET    | Get mutual followers             |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/mutual-following``            | GET    | Check mutual following           |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/follow-stats/count``          | GET    | Get follow statistics            |
+---------------------------------------------+--------+----------------------------------+

User Status
-----------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/users/{username}/status``                | GET    | Get user's status by username    |
+---------------------------------------------+--------+----------------------------------+

----

Profile APIs
============

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/profile``                                | GET    | Get my profile                   |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile``                                | PATCH  | Update my profile                |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile/details``                        | GET    | Get detailed profile info        |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile/activity``                       | GET    | Get my activity stats            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile/user/posts``                     | GET    | Get my posts                     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile/user/comments``                  | GET    | Get my comments                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile/user/tags``                      | GET    | Get my tags                      |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile/upload/avatar``                  | POST   | Upload avatar image              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/profile/upload/cover-image``             | POST   | Upload cover image               |
+---------------------------------------------+--------+----------------------------------+

**Update Profile Request:**

.. code-block:: json

    {
        "name": "John Doe",
        "bio": "Full-stack developer passionate about Laravel",
        "location": "San Francisco, CA",
        "website": "https://johndoe.dev",
        "github": "johndoe",
        "linkedin": "johndoe"
    }

----

Followers APIs
==============

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/users/{id}/follow``                      | POST   | Follow a user                    |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/users/{id}/unfollow``                    | POST   | Unfollow a user                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/followers/suggestions``                  | GET    | Get follow suggestions           |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/followers/my-followers``                 | GET    | Get my followers                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/followers/my-following``                 | GET    | Get who I'm following            |
+---------------------------------------------+--------+----------------------------------+

----

Tags APIs
=========

Tag Management
--------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/tags``                                   | GET    | Get all tags                     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/tags``                                   | POST   | Create new tag                   |
+---------------------------------------------+--------+----------------------------------+
| ``/tags/popular``                           | GET    | Get popular tags                 |
+---------------------------------------------+--------+----------------------------------+

Post Tags
---------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/posts/{postId}/tags``                    | POST   | Attach tags to post              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/posts/{postId}/tags/{tagId}``            | DELETE | Detach tag from post             |
+---------------------------------------------+--------+----------------------------------+

Tag Following
-------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/tags/{id}/follow``                       | POST   | Follow a tag                     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/tags/{id}/unfollow``                     | DELETE | Unfollow a tag                   |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/tags/{id}/followers``                    | GET    | Get tag followers                |
+---------------------------------------------+--------+----------------------------------+

----

Search APIs
===========

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/search/posts``                           | GET    | Search posts                     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/search/users``                           | GET    | Search users by username         |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/search/tags``                            | GET    | Search tags                      |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/search/histories``                       | GET    | Get search history               |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/search/clear``                           | DELETE | Clear search history             |
+---------------------------------------------+--------+----------------------------------+

**Search Query Parameters:**

.. code-block:: text

    GET /search/posts?q=laravel&page=1&per_page=15

----

Notifications APIs
=====================

Get Notifications
-----------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/notifications/all``                      | GET    | Get all notifications            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/comments``                 | GET    | Get comment notifications        |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/reacts``                   | GET    | Get reaction notifications       |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/new-followers``            | GET    | Get new follower notifications   |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/post-created``             | GET    | Get new post notifications       |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/mention``                  | GET    | Get mention notifications        |
+---------------------------------------------+--------+----------------------------------+

Notification Actions
--------------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/notifications/mark-as-read``             | POST   | Mark all as read                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/{id}/mark-as-read``        | POST   | Mark single as read              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/clear``                    | DELETE | Clear all notifications          |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/followers/clear``          | DELETE | Clear follower notifications     |
+---------------------------------------------+--------+----------------------------------+

Notification Preferences
------------------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/notifications/preferences``              | GET    | Get notification preferences     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/preferences``              | PUT    | Update all preferences           |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/notifications/preferences/{type}/toggle``| PATCH  | Toggle specific preference       |
+---------------------------------------------+--------+----------------------------------+

----

Reading Lists APIs
==================

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/reading-lists/lists/posts``              | GET    | Get all reading lists            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists``                          | POST   | Create reading list              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}``                     | GET    | Get single reading list          |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}``                     | PATCH  | Update reading list              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}``                     | DELETE | Delete reading list              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}/add-post/{postId}``   | POST   | Add post to list                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}/remove-post/{postId}``| DELETE | Remove post from list            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}/move-post/{postId}``  | POST   | Move post to another list        |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}/add-note/{postId}``   | POST   | Add note to post in list         |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}/delete-note/{postId}``| DELETE | Delete note from post            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}/show-notes/{postId}`` | GET    | Show notes for post              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reading-lists/{id}/duplicate``           | POST   | Duplicate reading list           |
+---------------------------------------------+--------+----------------------------------+

**Create Reading List:**

.. code-block:: json

    {
        "title": "Must Read Articles",
        "description": "Collection of important articles"
    }

**Add Note:**

.. code-block:: json

    {
        "note": "Remember to review this section"
    }

----

Saved Posts APIs
================

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/saved-posts``                            | GET    | Get all saved posts              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/saved-posts/{postId}``                   | POST   | Save a post                      |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/saved-posts/{postId}``                   | DELETE | Unsave a post                    |
+---------------------------------------------+--------+----------------------------------+

----

Code Editor APIs
===================

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/code/runtimes``                          | GET    | Get available runtimes           |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/code/execute``                           | POST   | Execute code                     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/code/search-runtimes``                   | GET    | Search runtimes                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/code/languages``                         | GET    | Get supported languages          |
+---------------------------------------------+--------+----------------------------------+

**Execute Code Request:**

.. code-block:: json

    {
        "language": "python",
        "version": "3.10.0",
        "code": "print('Hello, World!')",
        "stdin": "",
        "timeout": 30
    }

**Execute Code Response:**

.. code-block:: json

    {
        "success": true,
        "language": "python",
        "version": "3.10.0",
        "run": {
            "signal": null,
            "stdout": "Hello, World!\n",
            "stderr": "",
            "code": 0,
            "output": "Hello, World!\n",
            "memory": 5556000,
            "message": null,
            "status": null,
            "cpu_time": 31,
            "wall_time": 51
        }
    }

**Supported Languages (50+):**
Python, JavaScript, TypeScript, Java, C, C++, C#, Go, Rust, Ruby, PHP, Swift, Kotlin, Scala, R, Perl, Lua, Haskell, Elixir, and many more.

----

AI APIs
==========

Post AI Features
----------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/ai/summarize/post/{postId}``             | POST   | Summarize a post                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai/summarize/llama/post/{postId}``       | POST   | Summarize using LLama            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai/translate/post/{postId}``             | POST   | Translate a post                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai/analyze/post/{postId}``               | POST   | Analyze post content             |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai/question/post/{postId}``              | POST   | Ask question about post          |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai/generate/content``                    | POST   | Generate content                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai/summarize/post/languages``            | GET    | Get supported languages          |
+---------------------------------------------+--------+----------------------------------+

**Translate Request:**

.. code-block:: json

    {
        "target_language": "es"
    }

**Question Request:**

.. code-block:: json

    {
        "question": "What is the main point of this article?"
    }

**Generate Content Request:**

.. code-block:: json

    {
        "prompt": "Write an introduction about React hooks",
        "max_length": 500
    }

----

AI Chat APIs
===============

Chat
----

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/ai-chat/send``                           | POST   | Send chat message                |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/models``                         | GET    | Get available AI models          |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/attachments/upload``             | POST   | Upload attachment                |
+---------------------------------------------+--------+----------------------------------+

Chat History
------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/ai-chat/history/sessions``               | GET    | List all sessions                |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/create``        | POST   | Create new session               |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/{id}``          | GET    | Get session details              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/{id}``          | DELETE | Delete session                   |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/{id}/pin``      | POST   | Pin session                      |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/{id}/unpin``    | POST   | Unpin session                    |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/{id}/close``    | POST   | Close session                    |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/{id}/activate`` | POST   | Activate session                 |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/ai-chat/history/sessions/{id}/title``    | PUT    | Update session title             |
+---------------------------------------------+--------+----------------------------------+

**Chat Request:**

.. code-block:: json

    {
        "message": "Explain how async/await works in JavaScript",
        "session_id": "uuid-session-id",
        "model": "llama"
    }

----

User Status APIs
================

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/user/statuses``                          | GET    | Get my statuses                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/user/statuses``                          | POST   | Create status                    |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/user/statuses``                          | PATCH  | Update status                    |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/user/statuses``                          | DELETE | Delete status                    |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/user/statuses/set-busy``                 | POST   | Set busy status                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/user/statuses/set-available``            | POST   | Set available status             |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/user/statuses/clear-expired``            | POST   | Clear expired statuses           |
+---------------------------------------------+--------+----------------------------------+

**Create Status Request:**

.. code-block:: json

    {
        "status": "Working on a new project",
        "emoji": ":computer:",
        "expires_at": "2026-02-18T12:00:00Z"
    }

----

Reports & Blocking APIs
==========================

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/reports/block/{userId}``                 | POST   | Block a user                     |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reports/unblock/{userId}``               | POST   | Unblock a user                   |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reports/report/{targetId}``              | POST   | Report user/content              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reports/blocked-users``                  | GET    | Get blocked users list           |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/reports/reasons``                        | GET    | Get report reasons               |
+---------------------------------------------+--------+----------------------------------+

**Report Request:**

.. code-block:: json

    {
        "reason": "spam",
        "description": "This user is posting spam content"
    }

----

Settings APIs
=============

Password & Account
------------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/settings/update-password``               | PATCH  | Update password                  |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/settings/social-accounts``               | POST   | Add social accounts              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/settings/soft/delete-account``           | DELETE | Soft delete account              |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/settings/force/delete-account``          | DELETE | Permanently delete account       |
+---------------------------------------------+--------+----------------------------------+

Alternative Email
-----------------

+---------------------------------------------+--------+----------------------------------+
| URL                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/api/v1/settings/alt-email/send-otp``            | POST   | Add alternative email            |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/settings/alt-email/verify-otp``          | POST   | Verify alternative email         |
+---------------------------------------------+--------+----------------------------------+
| ``/api/v1/settings/alt-email/remove``              | DELETE | Remove alternative email         |
+---------------------------------------------+--------+----------------------------------+

**Update Password Request:**

.. code-block:: json

    {
        "current_password": "old-password",
        "password": "new-password",
        "password_confirmation": "new-password"
    }

**PATCH /api/v1/settings/update-password - Update Password**

Request:

.. code-block:: json

    {
        "current_password": "OldPass123!",
        "password": "NewSecurePass456!",
        "password_confirmation": "NewSecurePass456!"
    }

Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Password updated successfully"
    }

Error Response (422 Validation Error):

.. code-block:: json

    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "current_password": ["The current password is incorrect."]
        }
    }

**POST /api/v1/settings/social-accounts - Add Social Accounts**

Request:

.. code-block:: json

    {
        "github": "johndoe",
        "linkedin": "johndoe",
        "twitter": "johndoe_dev"
    }

Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Social accounts updated successfully"
    }

**POST /api/v1/settings/alt-email/send-otp - Add Alternative Email**

Request:

.. code-block:: json

    {
        "alt_email": "john.backup@example.com"
    }

Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "OTP sent to alternative email"
    }

**POST /api/v1/settings/alt-email/verify-otp - Verify Alternative Email**

Request:

.. code-block:: json

    {
        "alt_email": "john.backup@example.com",
        "otp": "123456"
    }

Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Alternative email verified successfully"
    }

**DELETE /api/v1/settings/soft/delete-account - Soft Delete Account**

Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Account deactivated. You can reactivate within 30 days."
    }

**DELETE /api/v1/settings/force/delete-account - Permanently Delete Account**

Request:

.. code-block:: json

    {
        "password": "CurrentPassword123!",
        "confirmation": "DELETE"
    }

Success Response (200 OK):

.. code-block:: json

    {
        "success": true,
        "message": "Account permanently deleted"
    }

----

Error Handling
=================

HTTP Status Codes
-----------------

+--------+----------------------------------+
| Code   | Description                      |
+========+==================================+
| 200    | Success                          |
+--------+----------------------------------+
| 201    | Created                          |
+--------+----------------------------------+
| 400    | Bad Request                      |
+--------+----------------------------------+
| 401    | Unauthorized                     |
+--------+----------------------------------+
| 403    | Forbidden                        |
+--------+----------------------------------+
| 404    | Not Found                        |
+--------+----------------------------------+
| 409    | Conflict                         |
+--------+----------------------------------+
| 422    | Validation Error                 |
+--------+----------------------------------+
| 429    | Too Many Requests                |
+--------+----------------------------------+
| 500    | Internal Server Error            |
+--------+----------------------------------+
| 504    | Gateway Timeout                  |
+--------+----------------------------------+

Error Response Format
---------------------

.. code-block:: json

    {
        "success": false,
        "message": "Error description",
        "error": "Detailed error message",
        "errors": {
            "field_name": ["Validation error message"]
        }
    }

----

Project Structure
====================

.. code-block:: text

    devhub/
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/V1/    # API Controllers
    │   │   │   ├── Auth/          # Authentication
    │   │   │   ├── AiModels/      # AI Features
    │   │   │   └── ...            # Other Controllers
    │   │   ├── Requests/          # Form Requests
    │   │   └── Resources/         # API Resources
    │   ├── Models/                # Eloquent Models
    │   ├── Services/              # Business Logic
    │   ├── Jobs/                  # Queue Jobs
    │   ├── Mail/                  # Email Templates
    │   ├── Notifications/         # Notifications
    │   └── Policies/              # Authorization
    ├── config/                    # Configuration
    ├── database/
    │   ├── migrations/            # Migrations
    │   └── seeders/               # Seeders
    ├── routes/
    │   └── api.php                # API Routes
    ├── tests/
    │   ├── Feature/               # Feature Tests
    │   └── Unit/                  # Unit Tests
    └── docs/                      # Documentation

----

Docker Deployment
=================

Using Docker
------------

.. code-block:: bash

    # Build the image
    docker build -t devhub .

    # Run the container
    docker run -p 8000:8000 devhub

Using Deploy Script
-------------------

.. code-block:: bash

    ./deploy.sh

Docker Compose
--------------

.. code-block:: yaml

    version: '3.8'
    services:
      app:
        build: .
        ports:
          - "8000:8000"
        environment:
          - APP_ENV=production
        volumes:
          - ./storage:/var/www/html/storage

----

Testing
==========

.. code-block:: bash

    # Run all tests
    php artisan test

    # Run with coverage
    php artisan test --coverage

    # Run specific test
    php artisan test tests/Feature/PostTest.php

----

Debugging
============

**Laravel Telescope**
    Access at ``/telescope`` for request monitoring, queries, jobs, and more.

**Log Viewer**
    Access at ``/log-viewer`` for application logs.

----

Contributing
============

We welcome contributions! Please follow these steps:

#. Fork the repository
#. Create a feature branch (``git checkout -b feature/amazing-feature``)
#. Commit your changes (``git commit -m 'Add amazing feature'``)
#. Push to the branch (``git push origin feature/amazing-feature``)
#. Open a Pull Request

----

License
=======

This project is licensed under the MIT License - see the `LICENSE <LICENSE>`_ file for details.

----

Support
==========

- **Production API:** http://devhub.eu-north-1.elasticbeanstalk.com/api/v1
- **API Documentation:** https://0yviq6a5i5.apidog.io/
- **Issues:** Open a GitHub issue
- **Email:** youssef.ahmed.fci@gmail.com

----

**Built with love for the developer community**

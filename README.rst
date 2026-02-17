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

| **Production URL:** http://devhub.eu-north-1.elasticbeanstalk.com
| **API Documentation:** https://0yviq6a5i5.apidog.io/

.. contents:: Table of Contents
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

Authentication APIs
===================

Public Endpoints (No Auth Required)
-----------------------------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/login``                       | POST   | User login                       |
+----------------------------------+--------+----------------------------------+
| ``/register``                    | POST   | User registration                |
+----------------------------------+--------+----------------------------------+
| ``/auth/google/login``           | POST   | Login with Google                |
+----------------------------------+--------+----------------------------------+
| ``/auth/google/callback``        | GET    | Google OAuth callback            |
+----------------------------------+--------+----------------------------------+
| ``/auth/github/login``           | POST   | Login with GitHub                |
+----------------------------------+--------+----------------------------------+
| ``/auth/github/callback``        | GET    | GitHub OAuth callback            |
+----------------------------------+--------+--------------------------------+

Email Verification
------------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/email/send-otp``              | POST   | Send verification OTP            |
+----------------------------------+--------+----------------------------------+
| ``/email/verify-otp``            | POST   | Verify email with OTP            |
+----------------------------------+--------+----------------------------------+
| ``/email/is-verified``           | GET    | Check verification status        |
+----------------------------------+--------+----------------------------------+

Password Reset
--------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/password/forgot``             | POST   | Request password reset           |
+----------------------------------+--------+----------------------------------+
| ``/password/verify-otp``         | POST   | Verify reset OTP                 |
+----------------------------------+--------+----------------------------------+
| ``/password/reset``              | POST   | Reset password                   |
+----------------------------------+--------+----------------------------------+

Protected Auth Endpoints
------------------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/logout``                      | POST   | Logout user                      |
+----------------------------------+--------+----------------------------------+
| ``/refresh``                     | POST   | Refresh JWT token                |
+----------------------------------+--------+----------------------------------+
| ``/me``                          | GET    | Get current user info            |
+----------------------------------+--------+----------------------------------+

**Login Request Example:**

.. code-block:: json

    {
        "email": "user@example.com",
        "password": "your-password"
    }

**Login Response Example:**

.. code-block:: json

    {
        "success": true,
        "message": "Login successful",
        "data": {
            "user": {
                "id": 1,
                "name": "John Doe",
                "email": "user@example.com"
            },
            "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "token_type": "bearer",
            "expires_in": 3600
        }
    }

----

Posts APIs
==========

CRUD Operations
---------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/posts``                       | GET    | List all posts (paginated)       |
+----------------------------------+--------+----------------------------------+
| ``/posts``                       | POST   | Create new post                  |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}``                  | GET    | Get single post                  |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}``                  | PUT    | Update post                      |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}``                  | PATCH  | Partial update post              |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}``                  | DELETE | Soft delete post                 |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}/force``            | DELETE | Permanently delete post          |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}/restore``          | POST   | Restore deleted post             |
+----------------------------------+--------+----------------------------------+

Post Queries
------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/user/posts``                  | GET    | Get current user's posts         |
+----------------------------------+--------+----------------------------------+
| ``/posts/recent``                | GET    | Get recent posts                 |
+----------------------------------+--------+----------------------------------+
| ``/posts/top-views``             | GET    | Get top viewed posts             |
+----------------------------------+--------+----------------------------------+
| ``/posts/drafts``                | GET    | Get user's draft posts           |
+----------------------------------+--------+----------------------------------+
| ``/posts/archives``              | GET    | Get archived/trashed posts       |
+----------------------------------+--------+----------------------------------+

Post Tags & Comments
--------------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/posts/{id}/tags``             | GET    | Get post tags (detailed)         |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}/tags-list``        | GET    | Get post tags (list)             |
+----------------------------------+--------+----------------------------------+
| ``/posts/{id}/comments``         | GET    | Get post comments                |
+----------------------------------+--------+----------------------------------+

Post Reporting
--------------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/posts/{id}/report``           | POST   | Report a post                    |
+----------------------------------+--------+----------------------------------+
| ``/posts/report/reasons``        | GET    | Get report reasons               |
+----------------------------------+--------+----------------------------------+

Post Views
----------

+----------------------------------+--------+----------------------------------+
| Endpoint                         | Method | Description                      |
+==================================+========+==================================+
| ``/posts/viewed/recent``         | GET    | Get recently viewed posts        |
+----------------------------------+--------+----------------------------------+
| ``/posts/viewed/clear``          | DELETE | Clear viewed posts history       |
+----------------------------------+--------+----------------------------------+

**Create Post Request:**

.. code-block:: json

    {
        "title": "Getting Started with Laravel",
        "content": "Laravel is a powerful PHP framework...",
        "status": "published",
        "tags": ["laravel", "php", "tutorial"]
    }

----

Comments APIs
=============

Create & Reply
--------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/posts/{postId}/comments``                | POST   | Create comment on post           |
+---------------------------------------------+--------+----------------------------------+
| ``/posts/{postId}/comments/{commentId}/reply`` | POST | Reply to a comment               |
+---------------------------------------------+--------+----------------------------------+

Get Comments
------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/posts/{postId}/comments``                | GET    | Get comments for a post          |
+---------------------------------------------+--------+----------------------------------+
| ``/posts/{postId}/comments/count``          | GET    | Get comment count for post       |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{userId}/comments``                | GET    | Get comments by user             |
+---------------------------------------------+--------+----------------------------------+
| ``/comments/{id}/replies``                  | GET    | Get replies to a comment         |
+---------------------------------------------+--------+----------------------------------+
| ``/comments/{id}/thread``                   | GET    | Get full comment thread          |
+---------------------------------------------+--------+----------------------------------+

Comment Actions
---------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/comments/{id}/pin``                      | POST   | Pin a comment                    |
+---------------------------------------------+--------+----------------------------------+
| ``/comments/{id}/unpin``                    | POST   | Unpin a comment                  |
+---------------------------------------------+--------+----------------------------------+
| ``/comments/{id}/force``                    | DELETE | Delete comment permanently       |
+---------------------------------------------+--------+----------------------------------+

Comment Reactions
-----------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/comments/{id}/react``                    | POST   | React to a comment               |
+---------------------------------------------+--------+----------------------------------+
| ``/comments/{id}/remove-react``             | DELETE | Remove reaction from comment     |
+---------------------------------------------+--------+----------------------------------+
| ``/comments/{id}/my-reaction``              | GET    | Get my reaction on comment       |
+---------------------------------------------+--------+----------------------------------+
| ``/comments/{id}/reactions``                | GET    | Get all reactions on comment     |
+---------------------------------------------+--------+----------------------------------+

My Comments
-----------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/my/comments``                            | GET    | Get my recent comments           |
+---------------------------------------------+--------+----------------------------------+
| ``/my/comments/stats``                      | GET    | Get my comment statistics        |
+---------------------------------------------+--------+----------------------------------+

----

Reactions APIs
=================

Post Reactions
--------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/posts/{id}/react``                       | POST   | React to a post                  |
+---------------------------------------------+--------+----------------------------------+
| ``/posts/{id}/remove-react``                | DELETE | Remove reaction from post        |
+---------------------------------------------+--------+----------------------------------+
| ``/posts/{id}/my-reaction``                 | GET    | Get my reaction on post          |
+---------------------------------------------+--------+----------------------------------+
| ``/posts/{id}/reactions-count``             | GET    | Get reaction counts              |
+---------------------------------------------+--------+----------------------------------+
| ``/posts/{id}/reactors``                    | GET    | Get list of reactors             |
+---------------------------------------------+--------+----------------------------------+
| ``/user/posts/total-reactions``             | GET    | Get total reactions on my posts  |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/users``                                  | GET    | List all users                   |
+---------------------------------------------+--------+----------------------------------+
| ``/users/recommended``                      | GET    | Get recommended users            |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}``                             | GET    | Get user profile                 |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/similar-skills``              | GET    | Get users with similar skills    |
+---------------------------------------------+--------+----------------------------------+

User Content
------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/users/{id}/posts``                       | GET    | Get user's posts                 |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/comments``                    | GET    | Get user's comments              |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/tags``                        | GET    | Get user's followed tags         |
+---------------------------------------------+--------+----------------------------------+

User Followers
--------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/users/{id}/followers``                   | GET    | Get user's followers             |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/followers/count``             | GET    | Get followers count              |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/following``                   | GET    | Get users being followed         |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/mutual-followers``            | GET    | Get mutual followers             |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/mutual-following``            | GET    | Check mutual following           |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/follow-stats/count``          | GET    | Get follow statistics            |
+---------------------------------------------+--------+----------------------------------+

User Status
-----------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/users/{username}/status``                | GET    | Get user's status by username    |
+---------------------------------------------+--------+----------------------------------+

----

Profile APIs
============

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/profile``                                | GET    | Get my profile                   |
+---------------------------------------------+--------+----------------------------------+
| ``/profile``                                | PATCH  | Update my profile                |
+---------------------------------------------+--------+----------------------------------+
| ``/profile/details``                        | GET    | Get detailed profile info        |
+---------------------------------------------+--------+----------------------------------+
| ``/profile/activity``                       | GET    | Get my activity stats            |
+---------------------------------------------+--------+----------------------------------+
| ``/profile/user/posts``                     | GET    | Get my posts                     |
+---------------------------------------------+--------+----------------------------------+
| ``/profile/user/comments``                  | GET    | Get my comments                  |
+---------------------------------------------+--------+----------------------------------+
| ``/profile/user/tags``                      | GET    | Get my tags                      |
+---------------------------------------------+--------+----------------------------------+
| ``/profile/upload/avatar``                  | POST   | Upload avatar image              |
+---------------------------------------------+--------+----------------------------------+
| ``/profile/upload/cover-image``             | POST   | Upload cover image               |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/users/{id}/follow``                      | POST   | Follow a user                    |
+---------------------------------------------+--------+----------------------------------+
| ``/users/{id}/unfollow``                    | POST   | Unfollow a user                  |
+---------------------------------------------+--------+----------------------------------+
| ``/followers/suggestions``                  | GET    | Get follow suggestions           |
+---------------------------------------------+--------+----------------------------------+
| ``/followers/my-followers``                 | GET    | Get my followers                 |
+---------------------------------------------+--------+----------------------------------+
| ``/followers/my-following``                 | GET    | Get who I'm following            |
+---------------------------------------------+--------+----------------------------------+

----

Tags APIs
=========

Tag Management
--------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/tags``                                   | GET    | Get all tags                     |
+---------------------------------------------+--------+----------------------------------+
| ``/tags``                                   | POST   | Create new tag                   |
+---------------------------------------------+--------+----------------------------------+
| ``/tags/popular``                           | GET    | Get popular tags                 |
+---------------------------------------------+--------+----------------------------------+

Post Tags
---------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/posts/{postId}/tags``                    | POST   | Attach tags to post              |
+---------------------------------------------+--------+----------------------------------+
| ``/posts/{postId}/tags/{tagId}``            | DELETE | Detach tag from post             |
+---------------------------------------------+--------+----------------------------------+

Tag Following
-------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/tags/{id}/follow``                       | POST   | Follow a tag                     |
+---------------------------------------------+--------+----------------------------------+
| ``/tags/{id}/unfollow``                     | DELETE | Unfollow a tag                   |
+---------------------------------------------+--------+----------------------------------+
| ``/tags/{id}/followers``                    | GET    | Get tag followers                |
+---------------------------------------------+--------+----------------------------------+

----

Search APIs
===========

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/search/posts``                           | GET    | Search posts                     |
+---------------------------------------------+--------+----------------------------------+
| ``/search/users``                           | GET    | Search users by username         |
+---------------------------------------------+--------+----------------------------------+
| ``/search/tags``                            | GET    | Search tags                      |
+---------------------------------------------+--------+----------------------------------+
| ``/search/histories``                       | GET    | Get search history               |
+---------------------------------------------+--------+----------------------------------+
| ``/search/clear``                           | DELETE | Clear search history             |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/notifications/all``                      | GET    | Get all notifications            |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/comments``                 | GET    | Get comment notifications        |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/reacts``                   | GET    | Get reaction notifications       |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/new-followers``            | GET    | Get new follower notifications   |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/post-created``             | GET    | Get new post notifications       |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/mention``                  | GET    | Get mention notifications        |
+---------------------------------------------+--------+----------------------------------+

Notification Actions
--------------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/notifications/mark-as-read``             | POST   | Mark all as read                 |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/{id}/mark-as-read``        | POST   | Mark single as read              |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/clear``                    | DELETE | Clear all notifications          |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/followers/clear``          | DELETE | Clear follower notifications     |
+---------------------------------------------+--------+----------------------------------+

Notification Preferences
------------------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/notifications/preferences``              | GET    | Get notification preferences     |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/preferences``              | PUT    | Update all preferences           |
+---------------------------------------------+--------+----------------------------------+
| ``/notifications/preferences/{type}/toggle``| PATCH  | Toggle specific preference       |
+---------------------------------------------+--------+----------------------------------+

----

Reading Lists APIs
==================

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/reading-lists/lists/posts``              | GET    | Get all reading lists            |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists``                          | POST   | Create reading list              |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}``                     | GET    | Get single reading list          |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}``                     | PATCH  | Update reading list              |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}``                     | DELETE | Delete reading list              |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}/add-post/{postId}``   | POST   | Add post to list                 |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}/remove-post/{postId}``| DELETE | Remove post from list            |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}/move-post/{postId}``  | POST   | Move post to another list        |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}/add-note/{postId}``   | POST   | Add note to post in list         |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}/delete-note/{postId}``| DELETE | Delete note from post            |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}/show-notes/{postId}`` | GET    | Show notes for post              |
+---------------------------------------------+--------+----------------------------------+
| ``/reading-lists/{id}/duplicate``           | POST   | Duplicate reading list           |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/saved-posts``                            | GET    | Get all saved posts              |
+---------------------------------------------+--------+----------------------------------+
| ``/saved-posts/{postId}``                   | POST   | Save a post                      |
+---------------------------------------------+--------+----------------------------------+
| ``/saved-posts/{postId}``                   | DELETE | Unsave a post                    |
+---------------------------------------------+--------+----------------------------------+

----

Code Editor APIs
===================

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/code/runtimes``                          | GET    | Get available runtimes           |
+---------------------------------------------+--------+----------------------------------+
| ``/code/execute``                           | POST   | Execute code                     |
+---------------------------------------------+--------+----------------------------------+
| ``/code/search-runtimes``                   | GET    | Search runtimes                  |
+---------------------------------------------+--------+----------------------------------+
| ``/code/languages``                         | GET    | Get supported languages          |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/ai/summarize/post/{postId}``             | POST   | Summarize a post                 |
+---------------------------------------------+--------+----------------------------------+
| ``/ai/summarize/llama/post/{postId}``       | POST   | Summarize using LLama            |
+---------------------------------------------+--------+----------------------------------+
| ``/ai/translate/post/{postId}``             | POST   | Translate a post                 |
+---------------------------------------------+--------+----------------------------------+
| ``/ai/analyze/post/{postId}``               | POST   | Analyze post content             |
+---------------------------------------------+--------+----------------------------------+
| ``/ai/question/post/{postId}``              | POST   | Ask question about post          |
+---------------------------------------------+--------+----------------------------------+
| ``/ai/generate/content``                    | POST   | Generate content                 |
+---------------------------------------------+--------+----------------------------------+
| ``/ai/summarize/post/languages``            | GET    | Get supported languages          |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/ai-chat/send``                           | POST   | Send chat message                |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/models``                         | GET    | Get available AI models          |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/attachments/upload``             | POST   | Upload attachment                |
+---------------------------------------------+--------+----------------------------------+

Chat History
------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/ai-chat/history/sessions``               | GET    | List all sessions                |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/create``        | POST   | Create new session               |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/{id}``          | GET    | Get session details              |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/{id}``          | DELETE | Delete session                   |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/{id}/pin``      | POST   | Pin session                      |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/{id}/unpin``    | POST   | Unpin session                    |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/{id}/close``    | POST   | Close session                    |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/{id}/activate`` | POST   | Activate session                 |
+---------------------------------------------+--------+----------------------------------+
| ``/ai-chat/history/sessions/{id}/title``    | PUT    | Update session title             |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/user/statuses``                          | GET    | Get my statuses                  |
+---------------------------------------------+--------+----------------------------------+
| ``/user/statuses``                          | POST   | Create status                    |
+---------------------------------------------+--------+----------------------------------+
| ``/user/statuses``                          | PATCH  | Update status                    |
+---------------------------------------------+--------+----------------------------------+
| ``/user/statuses``                          | DELETE | Delete status                    |
+---------------------------------------------+--------+----------------------------------+
| ``/user/statuses/set-busy``                 | POST   | Set busy status                  |
+---------------------------------------------+--------+----------------------------------+
| ``/user/statuses/set-available``            | POST   | Set available status             |
+---------------------------------------------+--------+----------------------------------+
| ``/user/statuses/clear-expired``            | POST   | Clear expired statuses           |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/reports/block/{userId}``                 | POST   | Block a user                     |
+---------------------------------------------+--------+----------------------------------+
| ``/reports/unblock/{userId}``               | POST   | Unblock a user                   |
+---------------------------------------------+--------+----------------------------------+
| ``/reports/report/{targetId}``              | POST   | Report user/content              |
+---------------------------------------------+--------+----------------------------------+
| ``/reports/blocked-users``                  | GET    | Get blocked users list           |
+---------------------------------------------+--------+----------------------------------+
| ``/reports/reasons``                        | GET    | Get report reasons               |
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
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/settings/update-password``               | PATCH  | Update password                  |
+---------------------------------------------+--------+----------------------------------+
| ``/settings/social-accounts``               | POST   | Add social accounts              |
+---------------------------------------------+--------+----------------------------------+
| ``/settings/soft/delete-account``           | DELETE | Soft delete account              |
+---------------------------------------------+--------+----------------------------------+
| ``/settings/force/delete-account``          | DELETE | Permanently delete account       |
+---------------------------------------------+--------+----------------------------------+

Alternative Email
-----------------

+---------------------------------------------+--------+----------------------------------+
| Endpoint                                    | Method | Description                      |
+=============================================+========+==================================+
| ``/settings/alt-email/send-otp``            | POST   | Add alternative email            |
+---------------------------------------------+--------+----------------------------------+
| ``/settings/alt-email/verify-otp``          | POST   | Verify alternative email         |
+---------------------------------------------+--------+----------------------------------+
| ``/settings/alt-email/remove``              | DELETE | Remove alternative email         |
+---------------------------------------------+--------+----------------------------------+

**Update Password Request:**

.. code-block:: json

    {
        "current_password": "old-password",
        "password": "new-password",
        "password_confirmation": "new-password"
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

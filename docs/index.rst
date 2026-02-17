===============================
DevHub Community Documentation
===============================

Welcome to the official documentation for **DevHub Community** - a modern, feature-rich social platform built for developers to share knowledge, collaborate, and grow together.

.. contents:: Table of Contents
   :depth: 3
   :local:

------------

Introduction
============

DevHub is a developer-focused community platform built with Laravel 12 and cutting-edge web technologies. It provides a comprehensive suite of features for content creation, social interaction, and AI-powered tools.

**Key Technologies:**

- **Backend:** Laravel 12 (PHP 8.3+)
- **Authentication:** JWT (tymon/jwt-auth)
- **Search:** Algolia
- **Storage:** AWS S3, Cloudinary
- **AI Integration:** LLama, Piston Code Execution
- **Real-time:** Laravel Notifications

------------

Getting Started
===============

Prerequisites
-------------

- PHP 8.3 or higher
- Composer
- Node.js & npm
- Git
- MySQL/PostgreSQL/SQLite

Installation
------------

1. **Clone the repository**

   .. code-block:: bash

      git clone <repository-url>
      cd devhub

2. **Install PHP dependencies**

   .. code-block:: bash

      composer install

3. **Install JavaScript dependencies**

   .. code-block:: bash

      npm install

4. **Environment Setup**

   .. code-block:: bash

      cp .env.example .env
      php artisan key:generate

5. **Configure Environment Variables**

   Update your ``.env`` file with the following configurations:

   .. code-block:: text

      # Database
      DB_CONNECTION=mysql
      DB_HOST=127.0.0.1
      DB_PORT=3306
      DB_DATABASE=devhub
      DB_USERNAME=root
      DB_PASSWORD=

      # JWT Authentication
      JWT_SECRET=your-jwt-secret

      # Algolia Search
      ALGOLIA_APP_ID=your-app-id
      ALGOLIA_SECRET=your-secret

      # Cloudinary
      CLOUDINARY_URL=cloudinary://...

      # Piston Code Execution
      PISTON_API_URL=https://emkc.org/api/v2/piston
      PISTON_API_KEY=your-api-key

      # AI Services
      LLAMA_API_URL=your-llama-url
      LLAMA_KEY=your-llama-key

6. **Run Migrations**

   .. code-block:: bash

      php artisan migrate

7. **Generate JWT Secret**

   .. code-block:: bash

      php artisan jwt:secret

8. **Build Assets**

   .. code-block:: bash

      npm run build

9. **Start Development Server**

   .. code-block:: bash

      php artisan serve

------------

API Reference
=============

Base URL
--------

All API endpoints are prefixed with ``/api/v1``.

.. code-block:: text

   https://your-domain.com/api/v1

Rate Limiting
-------------

API requests are rate-limited to prevent abuse:

- **Authentication endpoints:** 15 requests per minute
- **Authenticated endpoints:** 25 requests per minute

Authentication
--------------

DevHub uses JWT (JSON Web Token) authentication. Include the token in the Authorization header:

.. code-block:: text

   Authorization: Bearer <your-jwt-token>

Register
~~~~~~~~

.. code-block:: text

   POST /api/v1/register

**Request Body:**

.. code-block:: json

   {
     "name": "John Doe",
     "username": "johndoe",
     "email": "john@example.com",
     "password": "password123",
     "password_confirmation": "password123"
   }

**Response:**

.. code-block:: json

   {
     "message": "Registration successful",
     "user": { ... },
     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1..."
   }

Login
~~~~~

.. code-block:: text

   POST /api/v1/login

**Request Body:**

.. code-block:: json

   {
     "email": "john@example.com",
     "password": "password123"
   }

**Response:**

.. code-block:: json

   {
     "message": "Login successful",
     "user": { ... },
     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1..."
   }

Logout
~~~~~~

.. code-block:: text

   POST /api/v1/logout

**Headers:** Authorization: Bearer <token>

Refresh Token
~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/refresh

**Headers:** Authorization: Bearer <token>

Get Current User
~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/me

**Headers:** Authorization: Bearer <token>

Social Authentication
~~~~~~~~~~~~~~~~~~~~~

DevHub supports OAuth login via:

- **Google:** ``POST /api/v1/auth/google/login``
- **GitHub:** ``POST /api/v1/auth/github/login``

Email Verification
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/email/send-otp      # Send OTP
   POST /api/v1/email/verify-otp    # Verify OTP
   GET  /api/v1/email/is-verified   # Check verification status

Password Reset
~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/password/forgot     # Request reset
   POST /api/v1/password/verify-otp # Verify OTP
   POST /api/v1/password/reset      # Reset password

------------

Posts
-----

Create Post
~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/posts

**Request Body:**

.. code-block:: json

   {
     "title": "Getting Started with Laravel",
     "content": "Laravel is a powerful PHP framework...",
     "status": "published",
     "tags": ["laravel", "php", "tutorial"]
   }

Get All Posts
~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts

**Query Parameters:**

- ``page`` - Page number (default: 1)
- ``per_page`` - Items per page (default: 15)

Get Single Post
~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/{post_id}

Update Post
~~~~~~~~~~~

.. code-block:: text

   PUT /api/v1/posts/{post_id}

Delete Post
~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/posts/{post_id}

Force Delete Post
~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/posts/{post_id}/force

Restore Post
~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/posts/{post_id}/restore

Get User Posts
~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/user/posts

Get Draft Posts
~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/drafts

Get Archived Posts
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/archives

Get Recent Posts
~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/recent

Get Top Viewed Posts
~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/top-views

Report Post
~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/posts/{post_id}/report

**Request Body:**

.. code-block:: json

   {
     "reason": "spam"
   }

------------

Comments
--------

Create Comment
~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/posts/{post_id}/comments

**Request Body:**

.. code-block:: json

   {
     "content": "Great article! Very helpful."
   }

Reply to Comment
~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/posts/{post_id}/comments/{comment_id}/reply

**Request Body:**

.. code-block:: json

   {
     "content": "Thank you for the feedback!"
   }

Get Post Comments
~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/{post_id}/comments

Get Comment Count
~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/{post_id}/comments/count

Get Comment Replies
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/comments/{comment_id}/replies

Get Comment Thread
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/comments/{comment_id}/thread

Pin/Unpin Comment
~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/comments/{comment_id}/pin
   POST /api/v1/comments/{comment_id}/unpin

Comment Reactions
~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST   /api/v1/comments/{comment_id}/react
   DELETE /api/v1/comments/{comment_id}/remove-react
   GET    /api/v1/comments/{comment_id}/my-reaction
   GET    /api/v1/comments/{comment_id}/reactions

Delete Comment
~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/comments/{comment_id}/force

My Comments
~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/my/comments
   GET /api/v1/my/comments/stats

------------

Reactions
---------

React to Post
~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/posts/{post_id}/react

**Request Body:**

.. code-block:: json

   {
     "reaction": "like"
   }

**Available Reactions:** ``like``, ``love``, ``clap``, ``insightful``

Remove Reaction
~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/posts/{post_id}/remove-react

Get My Reaction
~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/{post_id}/my-reaction

Get Reaction Counts
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/{post_id}/reactions-count

Get Reactors
~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/{post_id}/reactors

Total Reactions on User Posts
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/user/posts/total-reactions

------------

Users & Profiles
----------------

Get All Users
~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users

Get User Profile
~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}

Get Recommended Users
~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/recommended

Get Users with Similar Skills
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/similar-skills

Get User Posts
~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/posts

Get User Comments
~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/comments

Get User Tags
~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/tags

Profile Management
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET   /api/v1/profile           # Get profile
   PATCH /api/v1/profile           # Update profile
   GET   /api/v1/profile/details   # Get profile details
   GET   /api/v1/profile/activity  # Get activity stats

**Update Profile Request:**

.. code-block:: json

   {
     "name": "John Doe",
     "bio": "Full-stack developer",
     "location": "San Francisco, CA",
     "website": "https://johndoe.dev",
     "github": "johndoe",
     "linkedin": "johndoe"
   }

Upload Avatar
~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/profile/upload/avatar

**Form Data:** ``avatar`` (image file)

Upload Cover Image
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/profile/upload/cover-image

**Form Data:** ``cover_image`` (image file)

------------

Followers
---------

Follow User
~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/users/{user_id}/follow

Unfollow User
~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/users/{user_id}/unfollow

Get User Followers
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/followers

Get Followers Count
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/followers/count

Get User Following
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/following

Get Mutual Followers
~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/mutual-followers

Check Mutual Following
~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/mutual-following

Get Follow Stats
~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{user_id}/follow-stats/count

My Followers & Following
~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/followers/my-followers
   GET /api/v1/followers/my-following

Follow Suggestions
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/followers/suggestions

------------

Tags
----

Get All Tags
~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/tags

Get Popular Tags
~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/tags/popular

Create Tag
~~~~~~~~~~

.. code-block:: text

   POST /api/v1/tags

**Request Body:**

.. code-block:: json

   {
     "name": "javascript"
   }

Attach Tags to Post
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/posts/{post_id}/tags

**Request Body:**

.. code-block:: json

   {
     "tags": ["javascript", "react", "frontend"]
   }

Detach Tag from Post
~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/posts/{post_id}/tags/{tag_id}

Follow Tag
~~~~~~~~~~

.. code-block:: text

   POST /api/v1/tags/{tag_id}/follow

Unfollow Tag
~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/tags/{tag_id}/unfollow

Get Tag Followers
~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/tags/{tag_id}/followers

------------

Reading Lists
-------------

Get Reading Lists
~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/reading-lists/lists/posts

Create Reading List
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/reading-lists

**Request Body:**

.. code-block:: json

   {
     "title": "Must Read Articles",
     "description": "Collection of important articles"
   }

Get Reading List
~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/reading-lists/{reading_list_id}

Update Reading List
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   PATCH /api/v1/reading-lists/{reading_list_id}

Delete Reading List
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/reading-lists/{reading_list_id}

Add Post to Reading List
~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/reading-lists/{reading_list_id}/add-post/{post_id}

Remove Post from Reading List
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/reading-lists/{reading_list_id}/remove-post/{post_id}

Add Note to Post
~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/reading-lists/{reading_list_id}/add-note/{post_id}

**Request Body:**

.. code-block:: json

   {
     "note": "Remember to review this section"
   }

Delete Note
~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/reading-lists/{reading_list_id}/delete-note/{post_id}

Show Notes
~~~~~~~~~~

.. code-block:: text

   GET /api/v1/reading-lists/{reading_list_id}/show-notes/{post_id}

Duplicate Reading List
~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/reading-lists/{reading_list_id}/duplicate

------------

Saved Posts
-----------

Get Saved Posts
~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/saved-posts

Save Post
~~~~~~~~~

.. code-block:: text

   POST /api/v1/saved-posts/{post_id}

Unsave Post
~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/saved-posts/{post_id}

------------

Search
------

Search Posts
~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/search/posts?q={query}

Search Users
~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/search/users?q={query}

Search Tags
~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/search/tags?q={query}

Get Search History
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/search/histories

Clear Search History
~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/search/clear

------------

Notifications
-------------

Get All Notifications
~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/notifications/all

Get Comment Notifications
~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/notifications/comments

Get Reaction Notifications
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/notifications/reacts

Get New Followers Notifications
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/notifications/new-followers

Get Mention Notifications
~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/notifications/mention

Get New Post Notifications
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/notifications/post-created

Mark All as Read
~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/notifications/mark-as-read

Mark Single as Read
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/notifications/{notification_id}/mark-as-read

Clear All Notifications
~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/notifications/clear

Clear Follower Notifications
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/notifications/followers/clear

Notification Preferences
~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET   /api/v1/notifications/preferences
   PUT   /api/v1/notifications/preferences
   PATCH /api/v1/notifications/preferences/{type}/toggle

------------

Code Editor
-----------

The Code Editor feature allows users to execute code in multiple programming languages using the Piston API.

Get Available Runtimes
~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/code/runtimes

**Response:**

.. code-block:: json

   {
     "success": true,
     "data": [
       {
         "language": "python",
         "version": "3.10.0",
         "aliases": ["py", "python3"]
       },
       ...
     ],
     "count": 50
   }

Execute Code
~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/code/execute

**Request Body:**

.. code-block:: json

   {
     "language": "python",
     "version": "3.10.0",
     "code": "print('Hello, World!')",
     "stdin": "",
     "timeout": 30
   }

**Response:**

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

Search Runtimes
~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/code/search-runtimes?q={language}

**Query Parameters:**

- ``q`` or ``query`` or ``search`` or ``language`` - Search term

Get Supported Languages
~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/code/languages

**Response:**

.. code-block:: json

   {
     "success": true,
     "data": ["c", "c++", "go", "java", "javascript", "python", "ruby", ...],
     "count": 50
   }

------------

AI Features
-----------

Post Summarization
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/ai/summarize/post/{post_id}

Post Translation
~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/ai/translate/post/{post_id}

**Request Body:**

.. code-block:: json

   {
     "target_language": "es"
   }

Post Analysis
~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/ai/analyze/post/{post_id}

Question Answering
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/ai/question/post/{post_id}

**Request Body:**

.. code-block:: json

   {
     "question": "What is the main point of this article?"
   }

Content Generation
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/ai/generate/content

**Request Body:**

.. code-block:: json

   {
     "prompt": "Write an introduction about React hooks",
     "max_length": 500
   }

Get Supported Languages
~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/ai/summarize/post/languages

------------

AI Chat
-------

Send Chat Message
~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/ai-chat/send

**Request Body:**

.. code-block:: json

   {
     "message": "Explain how async/await works in JavaScript",
     "session_id": "uuid-session-id",
     "model": "llama"
   }

Get Available Models
~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/ai-chat/models

Upload Attachment
~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/ai-chat/attachments/upload

**Form Data:** ``file`` (document file)

Chat History
~~~~~~~~~~~~

.. code-block:: text

   GET    /api/v1/ai-chat/history/sessions                     # List sessions
   POST   /api/v1/ai-chat/history/sessions/create              # Create session
   GET    /api/v1/ai-chat/history/sessions/{session_id}        # Get session
   DELETE /api/v1/ai-chat/history/sessions/{session_id}        # Delete session
   POST   /api/v1/ai-chat/history/sessions/{session_id}/pin    # Pin session
   POST   /api/v1/ai-chat/history/sessions/{session_id}/unpin  # Unpin session
   POST   /api/v1/ai-chat/history/sessions/{session_id}/close  # Close session
   POST   /api/v1/ai-chat/history/sessions/{session_id}/activate # Activate session
   PUT    /api/v1/ai-chat/history/sessions/{session_id}/title  # Update title

------------

User Status
-----------

Get User Statuses
~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/user/statuses

Create Status
~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/user/statuses

**Request Body:**

.. code-block:: json

   {
     "status": "Working on a new project",
     "emoji": "💻",
     "expires_at": "2026-02-18T12:00:00Z"
   }

Update Status
~~~~~~~~~~~~~

.. code-block:: text

   PATCH /api/v1/user/statuses

Delete Status
~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/user/statuses

Set Busy Status
~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/user/statuses/set-busy

Set Available Status
~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/user/statuses/set-available

Get User Status by Username
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/users/{username}/status

Clear Expired Statuses
~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/user/statuses/clear-expired

------------

Reports & Blocking
------------------

Block User
~~~~~~~~~~

.. code-block:: text

   POST /api/v1/reports/block/{user_id}

Unblock User
~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/reports/unblock/{user_id}

Report User/Content
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/reports/report/{target_id}

**Request Body:**

.. code-block:: json

   {
     "reason": "spam",
     "description": "This user is posting spam content"
   }

Get Blocked Users
~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/reports/blocked-users

Get Report Reasons
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/reports/reasons

------------

Settings
--------

Update Password
~~~~~~~~~~~~~~~

.. code-block:: text

   PATCH /api/v1/settings/update-password

**Request Body:**

.. code-block:: json

   {
     "current_password": "oldpassword",
     "password": "newpassword",
     "password_confirmation": "newpassword"
   }

Add Social Accounts
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST /api/v1/settings/social-accounts

**Request Body:**

.. code-block:: json

   {
     "github": "username",
     "linkedin": "username",
     "twitter": "username"
   }

Soft Delete Account
~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/settings/soft/delete-account

Permanently Delete Account
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/settings/force/delete-account

Alternative Email
~~~~~~~~~~~~~~~~~

.. code-block:: text

   POST   /api/v1/settings/alt-email/send-otp   # Add alt email
   POST   /api/v1/settings/alt-email/verify-otp # Verify alt email
   DELETE /api/v1/settings/alt-email/remove     # Remove alt email

------------

Post Views
----------

Get Recent Viewed Posts
~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: text

   GET /api/v1/posts/viewed/recent

Clear Viewed Posts
~~~~~~~~~~~~~~~~~~

.. code-block:: text

   DELETE /api/v1/posts/viewed/clear

------------

Error Handling
==============

HTTP Status Codes
-----------------

DevHub API uses standard HTTP status codes:

.. list-table::
   :widths: 15 85
   :header-rows: 1

   * - Code
     - Description
   * - 200
     - Success
   * - 201
     - Created
   * - 400
     - Bad Request - Invalid input
   * - 401
     - Unauthorized - Invalid or missing token
   * - 403
     - Forbidden - Access denied
   * - 404
     - Not Found - Resource doesn't exist
   * - 409
     - Conflict - Resource already exists
   * - 422
     - Unprocessable Entity - Validation error
   * - 429
     - Too Many Requests - Rate limit exceeded
   * - 500
     - Internal Server Error
   * - 504
     - Gateway Timeout - External service timeout

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

------------

Models
======

User
----

.. code-block:: json

   {
     "id": 1,
     "name": "John Doe",
     "username": "johndoe",
     "email": "john@example.com",
     "avatar": "https://...",
     "cover_image": "https://...",
     "bio": "Full-stack developer",
     "location": "San Francisco, CA",
     "website": "https://johndoe.dev",
     "github": "johndoe",
     "linkedin": "johndoe",
     "email_verified_at": "2026-01-15T10:00:00Z",
     "created_at": "2026-01-01T00:00:00Z",
     "updated_at": "2026-02-17T12:00:00Z"
   }

Post
----

.. code-block:: json

   {
     "id": 1,
     "title": "Getting Started with Laravel",
     "slug": "getting-started-with-laravel",
     "content": "Laravel is a powerful PHP framework...",
     "status": "published",
     "user_id": 1,
     "views_count": 150,
     "reactions_count": 25,
     "comments_count": 10,
     "created_at": "2026-02-01T10:00:00Z",
     "updated_at": "2026-02-17T12:00:00Z",
     "user": { ... },
     "tags": [ ... ]
   }

Comment
-------

.. code-block:: json

   {
     "id": 1,
     "content": "Great article!",
     "post_id": 1,
     "user_id": 2,
     "parent_id": null,
     "is_pinned": false,
     "reactions_count": 5,
     "replies_count": 2,
     "created_at": "2026-02-15T14:30:00Z",
     "updated_at": "2026-02-15T14:30:00Z",
     "user": { ... }
   }

ReadingList
-----------

.. code-block:: json

   {
     "id": 1,
     "title": "Must Read Articles",
     "description": "Collection of important articles",
     "post_count": 5,
     "created_at": "2026-02-10T09:00:00Z",
     "updated_at": "2026-02-17T11:00:00Z",
     "posts": [ ... ]
   }

Tag
---

.. code-block:: json

   {
     "id": 1,
     "name": "laravel",
     "slug": "laravel",
     "posts_count": 150,
     "followers_count": 500,
     "created_at": "2026-01-01T00:00:00Z"
   }

------------

Configuration
=============

Environment Variables
---------------------

.. list-table::
   :widths: 30 70
   :header-rows: 1

   * - Variable
     - Description
   * - ``APP_NAME``
     - Application name
   * - ``APP_ENV``
     - Environment (local, staging, production)
   * - ``APP_DEBUG``
     - Enable debug mode
   * - ``APP_URL``
     - Application URL
   * - ``DB_CONNECTION``
     - Database driver
   * - ``DB_HOST``
     - Database host
   * - ``DB_DATABASE``
     - Database name
   * - ``JWT_SECRET``
     - JWT signing secret
   * - ``JWT_TTL``
     - Token time-to-live (minutes)
   * - ``ALGOLIA_APP_ID``
     - Algolia application ID
   * - ``ALGOLIA_SECRET``
     - Algolia API secret
   * - ``CLOUDINARY_URL``
     - Cloudinary configuration URL
   * - ``AWS_ACCESS_KEY_ID``
     - AWS access key
   * - ``AWS_SECRET_ACCESS_KEY``
     - AWS secret key
   * - ``AWS_DEFAULT_REGION``
     - AWS region
   * - ``AWS_BUCKET``
     - S3 bucket name
   * - ``PISTON_API_URL``
     - Piston code execution API URL
   * - ``PISTON_API_KEY``
     - Piston API key (optional)
   * - ``LLAMA_API_URL``
     - LLama AI API URL
   * - ``LLAMA_KEY``
     - LLama API key

------------

Docker Deployment
=================

Using Docker
------------

Build and run with Docker:

.. code-block:: bash

   docker build -t devhub .
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
         - DB_CONNECTION=mysql
       volumes:
         - ./storage:/var/www/html/storage

------------

Testing
=======

Running Tests
-------------

.. code-block:: bash

   # Run all tests
   php artisan test

   # Run with coverage
   php artisan test --coverage

   # Run specific test file
   php artisan test tests/Feature/PostTest.php

   # Run PHPUnit directly
   ./vendor/bin/phpunit

Test Structure
--------------

.. code-block:: text

   tests/
   ├── Feature/         # Feature/Integration tests
   │   ├── Auth/
   │   ├── Posts/
   │   └── ...
   └── Unit/            # Unit tests
       ├── Models/
       ├── Services/
       └── ...

------------

Debugging
=========

Laravel Telescope
-----------------

DevHub includes Laravel Telescope for debugging. Access it at:

.. code-block:: text

   https://your-domain.com/telescope

**Features:**

- Request monitoring
- Exception tracking
- Database query logging
- Job monitoring
- Email previews
- Cache operations

Log Viewer
----------

Access logs through OpCodes Log Viewer:

.. code-block:: text

   https://your-domain.com/log-viewer

------------

Support
=======

For support and questions:

- **Issues:** Open a GitHub issue
- **Documentation:** https://0yviq6a5i5.apidog.io/
- **Email:** Contact the development team

------------

License
=======

This project is licensed under the MIT License.

------------

**Built with ❤️ for the developer community**

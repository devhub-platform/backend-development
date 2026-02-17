DevHub API Reference
====================

.. contents:: Contents
   :depth: 2
   :local:

Overview
--------

**Production URL**
   ``https://devhub.eu-north-1.elasticbeanstalk.com/api/v1``

**Content-Type**
   ``application/json``

**Authentication**
   JWT Bearer Token

.. code-block:: text

   Authorization: Bearer <your_jwt_token>

**Rate Limiting**

+---------------------+---------------------+
| Endpoint Type       | Limit               |
+=====================+=====================+
| Public              | 15 requests/minute  |
+---------------------+---------------------+
| Authenticated       | 25 requests/minute  |
+---------------------+---------------------+

.. tip::

   For detailed request/response examples, see :doc:`api-examples`.

----

Authentication
--------------

User Login
~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/login``
   * - **Authentication**
     - Not required
   * - **Description**
     - Authenticate user and receive JWT access token

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "password": "your_password"
   }

**Success Response (200 OK):**

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
               "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
           },
           "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
           "token_type": "bearer",
           "expires_in": 3600
       }
   }

User Registration
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/register``
   * - **Authentication**
     - Not required
   * - **Description**
     - Create a new user account

**Request Body:**

.. code-block:: json

   {
       "name": "John Doe",
       "username": "johndoe",
       "email": "john@example.com",
       "password": "SecurePass123!",
       "password_confirmation": "SecurePass123!"
   }

**Success Response (201 Created):**

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

Social Authentication
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/auth/google/login``
     - POST
     - Initiate Google OAuth flow
   * - ``/api/v1/auth/google/callback``
     - GET
     - Handle Google OAuth callback
   * - ``/api/v1/auth/github/login``
     - POST
     - Initiate GitHub OAuth flow
   * - ``/api/v1/auth/github/callback``
     - GET
     - Handle GitHub OAuth callback

Email Verification
~~~~~~~~~~~~~~~~~~

Send Verification OTP
"""""""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/email/send-otp``
   * - **Authentication**
     - Not required

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "OTP sent successfully to your email"
   }

Verify Email OTP
""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/email/verify-otp``
   * - **Authentication**
     - Not required

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "otp": "123456"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Email verified successfully"
   }

Check Email Verification Status
"""""""""""""""""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/email/is-verified``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "is_verified": true,
           "verified_at": "2026-02-17T10:30:00Z"
       }
   }

Password Recovery
~~~~~~~~~~~~~~~~~

Request Password Reset
""""""""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/password/forgot``
   * - **Authentication**
     - Not required

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Password reset OTP sent to your email"
   }

Verify Password Reset OTP
"""""""""""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/password/verify-otp``
   * - **Authentication**
     - Not required

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "otp": "123456"
   }

Set New Password
""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/password/reset``
   * - **Authentication**
     - Not required

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "otp": "123456",
       "password": "NewSecurePass123!",
       "password_confirmation": "NewSecurePass123!"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Password reset successfully"
   }

Session Management
~~~~~~~~~~~~~~~~~~

User Logout
"""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/logout``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Successfully logged out"
   }

Refresh Access Token
""""""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/refresh``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
           "token_type": "bearer",
           "expires_in": 3600
       }
   }

Get Current User Profile
""""""""""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/me``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

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

Posts
-----

List All Posts
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/posts``
   * - **Authentication**
     - Not required
   * - **Query Parameters**
     - ``page`` (int), ``per_page`` (int, default: 15)

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": 1,
               "title": "Getting Started with Laravel",
               "slug": "getting-started-with-laravel",
               "content": "Laravel is a powerful PHP framework...",
               "status": "published",
               "views_count": 150,
               "reactions_count": 25,
               "comments_count": 10,
               "created_at": "2026-02-01T10:00:00Z",
               "user": {
                   "id": 1,
                   "name": "John Doe",
                   "username": "johndoe",
                   "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
               },
               "tags": [
                   {"id": 1, "name": "laravel"},
                   {"id": 2, "name": "php"}
               ]
           }
       ],
       "meta": {
           "current_page": 1,
           "last_page": 10,
           "per_page": 15,
           "total": 150
       }
   }

Create New Post
~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/posts``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "title": "My New Article",
       "content": "This is the content of my article...",
       "status": "published",
       "tags": ["javascript", "react"]
   }

**Success Response (201 Created):**

.. code-block:: json

   {
       "success": true,
       "message": "Post created successfully",
       "data": {
           "id": 25,
           "title": "My New Article",
           "slug": "my-new-article",
           "content": "This is the content of my article...",
           "status": "published",
           "created_at": "2026-02-17T14:30:00Z"
       }
   }

Get Single Post
~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/posts/{id}``
   * - **Authentication**
     - Not required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "id": 1,
           "title": "Getting Started with Laravel",
           "slug": "getting-started-with-laravel",
           "content": "Full content here...",
           "status": "published",
           "views_count": 150,
           "reactions_count": 25,
           "comments_count": 10,
           "created_at": "2026-02-01T10:00:00Z",
           "updated_at": "2026-02-15T12:00:00Z",
           "user": {
               "id": 1,
               "name": "John Doe",
               "username": "johndoe",
               "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
           },
           "tags": [
               {"id": 1, "name": "laravel", "slug": "laravel"},
               {"id": 2, "name": "php", "slug": "php"}
           ]
       }
   }

Update Existing Post
~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``PUT /api/v1/posts/{id}`` or ``PATCH /api/v1/posts/{id}``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "title": "Updated Title",
       "content": "Updated content...",
       "status": "published"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Post updated successfully",
       "data": {
           "id": 1,
           "title": "Updated Title",
           "content": "Updated content...",
           "updated_at": "2026-02-17T15:00:00Z"
       }
   }

Delete Post (Soft Delete)
~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``DELETE /api/v1/posts/{id}``
   * - **Authentication**
     - Required
   * - **Description**
     - Moves the post to trash (can be restored)

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Post deleted successfully"
   }

Delete Post Permanently
~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``DELETE /api/v1/posts/{id}/force``
   * - **Authentication**
     - Required
   * - **Description**
     - Permanently removes the post (cannot be restored)

Restore Deleted Post
~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/posts/{id}/restore``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Post restored successfully"
   }

Additional Post Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/user/posts``
     - GET
     - Get current user's posts
   * - ``/api/v1/posts/recent``
     - GET
     - Get recent posts
   * - ``/api/v1/posts/top-views``
     - GET
     - Get most viewed posts
   * - ``/api/v1/posts/drafts``
     - GET
     - Get user's draft posts
   * - ``/api/v1/posts/archives``
     - GET
     - Get archived/trashed posts
   * - ``/api/v1/posts/viewed/recent``
     - GET
     - Get recently viewed posts
   * - ``/api/v1/posts/viewed/clear``
     - DELETE
     - Clear viewing history

Post Tags Endpoints
~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/posts/{id}/tags``
     - GET
     - Get post tags with details
   * - ``/api/v1/posts/{id}/tags-list``
     - GET
     - Get post tags as simple list

Report a Post
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/posts/{id}/report``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "reason": "spam",
       "description": "This post contains spam content"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Post reported successfully"
   }

Get Report Reasons
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/posts/report/reasons``
   * - **Authentication**
     - Not required

----

Comments
--------

Create New Comment
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/posts/{postId}/comments``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "content": "Great article! Very helpful for beginners."
   }

**Success Response (201 Created):**

.. code-block:: json

   {
       "success": true,
       "message": "Comment created successfully",
       "data": {
           "id": 45,
           "content": "Great article! Very helpful for beginners.",
           "post_id": 1,
           "user_id": 5,
           "parent_id": null,
           "is_pinned": false,
           "reactions_count": 0,
           "created_at": "2026-02-17T15:30:00Z",
           "user": {
               "id": 5,
               "name": "Jane Smith",
               "username": "janesmith",
               "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
           }
       }
   }

Reply to a Comment
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/posts/{postId}/comments/{commentId}/reply``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "content": "Thank you for your feedback!"
   }

Get Post Comments
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/posts/{postId}/comments``
   * - **Authentication**
     - Not required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": 45,
               "content": "Great article!",
               "is_pinned": false,
               "reactions_count": 5,
               "replies_count": 2,
               "created_at": "2026-02-17T15:30:00Z",
               "user": {
                   "id": 5,
                   "name": "Jane Smith",
                   "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
               },
               "replies": [
                   {
                       "id": 46,
                       "content": "Thank you!",
                       "created_at": "2026-02-17T15:35:00Z"
                   }
               ]
           }
       ],
       "meta": {
           "total": 10,
           "current_page": 1
       }
   }

Additional Comment Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/posts/{postId}/comments/count``
     - GET
     - Get comment count for post
   * - ``/api/v1/users/{userId}/comments``
     - GET
     - Get comments by specific user
   * - ``/api/v1/comments/{id}/replies``
     - GET
     - Get all replies to a comment
   * - ``/api/v1/comments/{id}/thread``
     - GET
     - Get full comment thread
   * - ``/api/v1/comments/{id}/pin``
     - POST
     - Pin a comment
   * - ``/api/v1/comments/{id}/unpin``
     - POST
     - Unpin a comment
   * - ``/api/v1/comments/{id}/force``
     - DELETE
     - Permanently delete comment
   * - ``/api/v1/my/comments``
     - GET
     - Get my recent comments
   * - ``/api/v1/my/comments/stats``
     - GET
     - Get my comment statistics

Comment Reactions
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/comments/{id}/react``
     - POST
     - Add reaction to comment
   * - ``/api/v1/comments/{id}/remove-react``
     - DELETE
     - Remove reaction from comment
   * - ``/api/v1/comments/{id}/my-reaction``
     - GET
     - Get my reaction on comment
   * - ``/api/v1/comments/{id}/reactions``
     - GET
     - Get all reactions on comment

----

Reactions
---------

Add Reaction to Post
~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/posts/{id}/react``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "reaction": "love"
   }

**Available Reaction Types:**

- ``like`` - Standard like
- ``love`` - Heart reaction
- ``clap`` - Applause
- ``insightful`` - Valuable content
- ``celebrate`` - Celebration

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Reaction added successfully",
       "data": {
           "reaction": "love",
           "total_reactions": 26
       }
   }

Get Post Reaction Counts
~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/posts/{id}/reactions-count``
   * - **Authentication**
     - Not required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "like": 15,
           "love": 8,
           "clap": 3,
           "insightful": 2,
           "celebrate": 1,
           "total": 29
       }
   }

Additional Reaction Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/posts/{id}/remove-react``
     - DELETE
     - Remove reaction from post
   * - ``/api/v1/posts/{id}/my-reaction``
     - GET
     - Get my reaction on post
   * - ``/api/v1/posts/{id}/reactors``
     - GET
     - Get list of users who reacted
   * - ``/api/v1/user/posts/total-reactions``
     - GET
     - Get total reactions on my posts

----

Users
-----

List All Users
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/users``
   * - **Authentication**
     - Not required
   * - **Query Parameters**
     - ``page`` (int), ``per_page`` (int, default: 15)

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": 1,
               "name": "John Doe",
               "username": "johndoe",
               "avatar": "https://cloudinary.com/avatar.jpg",
               "bio": "Full-stack developer",
               "followers_count": 150,
               "following_count": 75,
               "posts_count": 25
           }
       ],
       "meta": {
           "current_page": 1,
           "total": 100
       }
   }

Get User by ID
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/users/{id}``
   * - **Authentication**
     - Not required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "id": 1,
           "name": "John Doe",
           "username": "johndoe",
           "email": "john@example.com",
           "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
           "cover_image": "https://res.cloudinary.com/devhub/image/cover.jpg",
           "bio": "Full-stack developer passionate about Laravel",
           "location": "San Francisco, CA",
           "website": "https://johndoe.dev",
           "github": "johndoe",
           "linkedin": "johndoe",
           "followers_count": 150,
           "following_count": 75,
           "posts_count": 25,
           "is_following": false,
           "is_followed_by": true,
           "created_at": "2026-01-01T00:00:00Z"
       }
   }

Additional User Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/users/recommended``
     - GET
     - Get recommended users to follow
   * - ``/api/v1/users/{id}/similar-skills``
     - GET
     - Get users with similar skills
   * - ``/api/v1/users/{id}/posts``
     - GET
     - Get posts by user
   * - ``/api/v1/users/{id}/comments``
     - GET
     - Get comments by user
   * - ``/api/v1/users/{id}/tags``
     - GET
     - Get tags followed by user
   * - ``/api/v1/users/{id}/followers``
     - GET
     - Get user's followers list
   * - ``/api/v1/users/{id}/followers/count``
     - GET
     - Get follower count
   * - ``/api/v1/users/{id}/following``
     - GET
     - Get users being followed
   * - ``/api/v1/users/{id}/mutual-followers``
     - GET
     - Get mutual followers
   * - ``/api/v1/users/{id}/mutual-following``
     - GET
     - Check if mutually following
   * - ``/api/v1/users/{id}/follow-stats/count``
     - GET
     - Get follow statistics
   * - ``/api/v1/users/{username}/status``
     - GET
     - Get user's current status

----

Profile
-------

Get My Profile
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/profile``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "id": 1,
           "name": "John Doe",
           "username": "johndoe",
           "email": "john@example.com",
           "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
           "cover_image": "https://res.cloudinary.com/devhub/image/cover.jpg",
           "bio": "Full-stack developer",
           "location": "San Francisco, CA",
           "website": "https://johndoe.dev",
           "github": "johndoe",
           "linkedin": "johndoe",
           "followers_count": 150,
           "following_count": 75,
           "posts_count": 25
       }
   }

Update My Profile
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``PATCH /api/v1/profile``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "name": "John Updated",
       "bio": "Senior Full-stack developer",
       "location": "New York, NY",
       "website": "https://johndoe.dev",
       "github": "johndoe",
       "linkedin": "johndoe"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Profile updated successfully",
       "data": {
           "id": 1,
           "name": "John Updated",
           "bio": "Senior Full-stack developer",
           "location": "New York, NY"
       }
   }

Upload Profile Avatar
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/profile/upload/avatar``
   * - **Authentication**
     - Required
   * - **Content-Type**
     - ``multipart/form-data``

**Request Body:**

.. code-block:: text

   avatar: [binary image file]

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Avatar uploaded successfully",
       "data": {
           "avatar": "https://res.cloudinary.com/devhub/image/new-avatar.jpg"
       }
   }

Upload Profile Cover Image
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/profile/upload/cover-image``
   * - **Authentication**
     - Required
   * - **Content-Type**
     - ``multipart/form-data``

**Request Body:**

.. code-block:: text

   cover_image: [binary image file]

Additional Profile Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/profile/details``
     - GET
     - Get detailed profile information
   * - ``/api/v1/profile/activity``
     - GET
     - Get my activity statistics
   * - ``/api/v1/profile/user/posts``
     - GET
     - Get my posts
   * - ``/api/v1/profile/user/comments``
     - GET
     - Get my comments
   * - ``/api/v1/profile/user/tags``
     - GET
     - Get my followed tags

----

Followers
---------

Follow a User
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/users/{id}/follow``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "You are now following this user",
       "data": {
           "following": true,
           "followers_count": 151
       }
   }

Unfollow a User
~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/users/{id}/unfollow``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "You have unfollowed this user",
       "data": {
           "following": false,
           "followers_count": 150
       }
   }

Additional Follower Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/followers/suggestions``
     - GET
     - Get follow suggestions
   * - ``/api/v1/followers/my-followers``
     - GET
     - Get my followers list
   * - ``/api/v1/followers/my-following``
     - GET
     - Get users I'm following

----

Tags
----

List All Tags
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/tags``
   * - **Authentication**
     - Not required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": 1,
               "name": "laravel",
               "slug": "laravel",
               "posts_count": 150,
               "followers_count": 500
           }
       ]
   }

Create New Tag
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/tags``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "name": "typescript"
   }

**Success Response (201 Created):**

.. code-block:: json

   {
       "success": true,
       "message": "Tag created successfully",
       "data": {
           "id": 50,
           "name": "typescript",
           "slug": "typescript"
       }
   }

Additional Tag Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/tags/popular``
     - GET
     - Get popular/trending tags
   * - ``/api/v1/posts/{postId}/tags``
     - POST
     - Attach tags to a post
   * - ``/api/v1/posts/{postId}/tags/{tagId}``
     - DELETE
     - Remove tag from post
   * - ``/api/v1/tags/{id}/follow``
     - POST
     - Follow a tag
   * - ``/api/v1/tags/{id}/unfollow``
     - DELETE
     - Unfollow a tag
   * - ``/api/v1/tags/{id}/followers``
     - GET
     - Get tag followers

----

Search
------

Search Posts
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/search/posts``
   * - **Authentication**
     - Not required
   * - **Query Parameters**
     - ``q`` (string, required), ``page`` (int), ``per_page`` (int)

**Example Request:**

.. code-block:: http

   GET /api/v1/search/posts?q=laravel&page=1&per_page=15

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": 1,
               "title": "Getting Started with Laravel",
               "slug": "getting-started-with-laravel",
               "excerpt": "Laravel is a powerful PHP framework...",
               "user": {
                   "id": 1,
                   "name": "John Doe",
                   "username": "johndoe"
               },
               "tags": ["laravel", "php"],
               "created_at": "2026-02-01T10:00:00Z"
           }
       ],
       "meta": {
           "query": "laravel",
           "total": 25,
           "current_page": 1
       }
   }

Additional Search Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/search/users``
     - GET
     - Search users by username or name
   * - ``/api/v1/search/tags``
     - GET
     - Search tags by name
   * - ``/api/v1/search/histories``
     - GET
     - Get recent search history
   * - ``/api/v1/search/clear``
     - DELETE
     - Clear search history

----

Notifications
-------------

Get All Notifications
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/notifications/all``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": "550e8400-e29b-41d4-a716-446655440000",
               "type": "comment",
               "message": "Jane Smith commented on your post",
               "data": {
                   "post_id": 1,
                   "comment_id": 45,
                   "user": {
                       "id": 5,
                       "name": "Jane Smith",
                       "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
                   }
               },
               "read_at": null,
               "created_at": "2026-02-17T15:30:00Z"
           }
       ],
       "meta": {
           "unread_count": 5,
           "total": 50
       }
   }

Additional Notification Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/notifications/comments``
     - GET
     - Get comment notifications
   * - ``/api/v1/notifications/reacts``
     - GET
     - Get reaction notifications
   * - ``/api/v1/notifications/new-followers``
     - GET
     - Get new follower notifications
   * - ``/api/v1/notifications/post-created``
     - GET
     - Get new post notifications
   * - ``/api/v1/notifications/mention``
     - GET
     - Get mention notifications
   * - ``/api/v1/notifications/mark-as-read``
     - POST
     - Mark all notifications as read
   * - ``/api/v1/notifications/{id}/mark-as-read``
     - POST
     - Mark single notification as read
   * - ``/api/v1/notifications/clear``
     - DELETE
     - Clear all notifications
   * - ``/api/v1/notifications/followers/clear``
     - DELETE
     - Clear follower notifications
   * - ``/api/v1/notifications/preferences``
     - GET
     - Get notification preferences
   * - ``/api/v1/notifications/preferences``
     - PUT
     - Update notification preferences
   * - ``/api/v1/notifications/preferences/{type}/toggle``
     - PATCH
     - Toggle specific preference

----

Reading Lists
-------------

Get Reading Lists
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /reading-lists/lists/posts``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": 1,
               "title": "Must Read Articles",
               "description": "Collection of important articles",
               "posts_count": 5,
               "created_at": "2026-02-10T09:00:00Z",
               "posts": [
                   {
                       "id": 1,
                       "title": "Getting Started with Laravel",
                       "added_at": "2026-02-10T09:30:00Z"
                   }
               ]
           }
       ]
   }

Create New Reading List
~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/reading-lists``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "title": "React Best Practices",
       "description": "Collection of React articles"
   }

**Success Response (201 Created):**

.. code-block:: json

   {
       "success": true,
       "message": "Reading list created successfully",
       "data": {
           "id": 5,
           "title": "React Best Practices",
           "description": "Collection of React articles",
           "posts_count": 0,
           "created_at": "2026-02-17T16:00:00Z"
       }
   }

Additional Reading List Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/reading-lists/{id}``
     - GET
     - Get single reading list
   * - ``/api/v1/reading-lists/{id}``
     - PATCH
     - Update reading list
   * - ``/api/v1/reading-lists/{id}``
     - DELETE
     - Delete reading list
   * - ``/api/v1/reading-lists/{id}/add-post/{postId}``
     - POST
     - Add post to list
   * - ``/api/v1/reading-lists/{id}/remove-post/{postId}``
     - DELETE
     - Remove post from list
   * - ``/api/v1/reading-lists/{id}/move-post/{postId}``
     - POST
     - Move post to another list
   * - ``/api/v1/reading-lists/{id}/add-note/{postId}``
     - POST
     - Add note to a post
   * - ``/api/v1/reading-lists/{id}/delete-note/{postId}``
     - DELETE
     - Delete note from post
   * - ``/api/v1/reading-lists/{id}/show-notes/{postId}``
     - GET
     - Show notes for a post
   * - ``/api/v1/reading-lists/{id}/duplicate``
     - POST
     - Duplicate reading list

----

Saved Posts
-----------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/saved-posts``
     - GET
     - Get all saved posts
   * - ``/api/v1/saved-posts/{postId}``
     - POST
     - Save a post to bookmarks
   * - ``/api/v1/saved-posts/{postId}``
     - DELETE
     - Remove post from bookmarks

**GET /api/v1/saved-posts Response:**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": 1,
               "post": {
                   "id": 25,
                   "title": "Understanding React Hooks",
                   "slug": "understanding-react-hooks",
                   "excerpt": "React hooks are...",
                   "user": {
                       "id": 5,
                       "name": "Jane Smith"
                   }
               },
               "saved_at": "2026-02-15T10:00:00Z"
           }
       ],
       "meta": {
           "total": 15
       }
   }

----

Code Editor
-----------

Get Available Runtimes
~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/code/runtimes``
   * - **Authentication**
     - Not required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "language": "python",
               "version": "3.10.0",
               "aliases": ["py", "python3", "py3"]
           },
           {
               "language": "javascript",
               "version": "18.15.0",
               "aliases": ["js", "node"]
           }
       ],
       "count": 50
   }

Execute Code
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/code/execute``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "language": "python",
       "version": "3.10.0",
       "code": "print('Hello, World!')",
       "stdin": "",
       "timeout": 30
   }

**Success Response (200 OK):**

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

Additional Code Editor Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/code/search-runtimes``
     - GET
     - Search runtimes by language
   * - ``/api/v1/code/languages``
     - GET
     - Get all supported languages

**Supported Languages (50+):** Python, JavaScript, TypeScript, Java, C, C++, C#, Go, Rust, Ruby, PHP, Swift, Kotlin, Scala, R, Perl, Lua, Haskell, Elixir, and more.

----

AI Features
-----------

Summarize Post Content
~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/ai/summarize/post/{postId}``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "original_length": 2500,
           "summary": "This article discusses the fundamentals of Laravel framework...",
           "summary_length": 350,
           "language": "en"
       }
   }

Translate Post Content
~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/ai/translate/post/{postId}``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "target_language": "es"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "original_language": "en",
           "target_language": "es",
           "translated_title": "Comenzando con Laravel",
           "translated_content": "Laravel es un poderoso framework de PHP..."
       }
   }

Analyze Post Content
~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/ai/analyze/post/{postId}``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "word_count": 1500,
           "reading_time": "6 minutes",
           "complexity": "intermediate",
           "topics": ["web development", "PHP", "Laravel"],
           "sentiment": "informative",
           "key_concepts": ["MVC", "routing", "migrations"]
       }
   }

Ask Question About Post
~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/ai/question/post/{postId}``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "question": "What are the main benefits mentioned in this article?"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "question": "What are the main benefits mentioned in this article?",
           "answer": "The article highlights several key benefits...",
           "confidence": 0.95
       }
   }

Generate AI Content
~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/ai/generate/content``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "prompt": "Write an introduction about React hooks for beginners",
       "max_length": 300,
       "tone": "educational"
   }

Additional AI Endpoints
~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/ai/summarize/llama/post/{postId}``
     - POST
     - Summarize using LLama model
   * - ``/api/v1/ai/summarize/post/languages``
     - GET
     - Get supported translation languages

----

AI Chat
-------

Send Chat Message
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/ai-chat/send``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "message": "How do I create a REST API in Laravel?",
       "session_id": "550e8400-e29b-41d4-a716-446655440000",
       "model": "llama"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "message_id": "msg-123",
           "response": "To create a REST API in Laravel, follow these steps...",
           "session_id": "550e8400-e29b-41d4-a716-446655440000",
           "model": "llama",
           "tokens_used": 350,
           "created_at": "2026-02-17T16:30:00Z"
       }
   }

Get Available AI Models
~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/ai-chat/models``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": "llama",
               "name": "LLama",
               "description": "General purpose AI assistant",
               "max_tokens": 4096
           },
           {
               "id": "code-llama",
               "name": "Code LLama",
               "description": "Specialized for code generation",
               "max_tokens": 8192
           }
       ]
   }

Additional AI Chat Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/ai-chat/attachments/upload``
     - POST
     - Upload file attachment
   * - ``/api/v1/ai-chat/history/sessions``
     - GET
     - List all chat sessions
   * - ``/api/v1/ai-chat/history/sessions/create``
     - POST
     - Create new chat session
   * - ``/api/v1/ai-chat/history/sessions/{id}``
     - GET
     - Get session details with messages
   * - ``/api/v1/ai-chat/history/sessions/{id}``
     - DELETE
     - Delete chat session
   * - ``/api/v1/ai-chat/history/sessions/{id}/pin``
     - POST
     - Pin session to top
   * - ``/api/v1/ai-chat/history/sessions/{id}/unpin``
     - POST
     - Unpin session
   * - ``/api/v1/ai-chat/history/sessions/{id}/close``
     - POST
     - Close/archive session
   * - ``/api/v1/ai-chat/history/sessions/{id}/activate``
     - POST
     - Reactivate closed session
   * - ``/api/v1/ai-chat/history/sessions/{id}/title``
     - PUT
     - Update session title

----

User Status
-----------

Get My Current Status
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``GET /api/v1/user/statuses``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "current_status": {
               "id": 1,
               "status": "Working on a new project",
               "emoji": ":computer:",
               "is_busy": false,
               "expires_at": "2026-02-18T12:00:00Z",
               "created_at": "2026-02-17T09:00:00Z"
           }
       }
   }

Set New Status
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/user/statuses``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "status": "In a meeting",
       "emoji": ":calendar:",
       "expires_at": "2026-02-17T18:00:00Z"
   }

Additional Status Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/user/statuses``
     - PATCH
     - Update current status
   * - ``/api/v1/user/statuses``
     - DELETE
     - Clear/delete status
   * - ``/api/v1/user/statuses/set-busy``
     - POST
     - Set status to busy
   * - ``/api/v1/user/statuses/set-available``
     - POST
     - Set status to available
   * - ``/api/v1/user/statuses/clear-expired``
     - POST
     - Clear all expired statuses

----

Reports & Blocking
------------------

Block a User
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/reports/block/{userId}``
   * - **Authentication**
     - Required

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "User blocked successfully"
   }

Report User or Content
~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``POST /api/v1/reports/report/{targetId}``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "reason": "harassment",
       "description": "This user has been sending inappropriate messages"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Report submitted successfully",
       "data": {
           "report_id": "rpt-550e8400-e29b",
           "status": "pending"
       }
   }

Additional Reports Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/reports/unblock/{userId}``
     - POST
     - Unblock a user
   * - ``/api/v1/reports/blocked-users``
     - GET
     - Get list of blocked users
   * - ``/api/v1/reports/reasons``
     - GET
     - Get available report reasons

----

Settings
--------

Update Account Password
~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **URL**
     - ``PATCH /api/v1/settings/update-password``
   * - **Authentication**
     - Required

**Request Body:**

.. code-block:: json

   {
       "current_password": "OldPass123!",
       "password": "NewSecurePass456!",
       "password_confirmation": "NewSecurePass456!"
   }

**Success Response (200 OK):**

.. code-block:: json

   {
       "success": true,
       "message": "Password updated successfully"
   }

Additional Settings Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - URL
     - Method
     - Description
   * - ``/api/v1/settings/social-accounts``
     - POST
     - Link social media accounts
   * - ``/api/v1/settings/soft/delete-account``
     - DELETE
     - Deactivate account (recoverable)
   * - ``/api/v1/settings/force/delete-account``
     - DELETE
     - Permanently delete account
   * - ``/api/v1/settings/alt-email/send-otp``
     - POST
     - Add alternative email address
   * - ``/api/v1/settings/alt-email/verify-otp``
     - POST
     - Verify alternative email
   * - ``/api/v1/settings/alt-email/remove``
     - DELETE
     - Remove alternative email

----

Error Handling
--------------

HTTP Status Codes
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 15 25 60
   :header-rows: 1

   * - Code
     - Status
     - Description
   * - 200
     - OK
     - Request completed successfully
   * - 201
     - Created
     - Resource created successfully
   * - 400
     - Bad Request
     - Invalid request format or parameters
   * - 401
     - Unauthorized
     - Authentication required or token invalid
   * - 403
     - Forbidden
     - Access denied to this resource
   * - 404
     - Not Found
     - Requested resource does not exist
   * - 409
     - Conflict
     - Resource conflict (e.g., duplicate entry)
   * - 422
     - Unprocessable Entity
     - Validation error in request data
   * - 429
     - Too Many Requests
     - Rate limit exceeded
   * - 500
     - Internal Server Error
     - Unexpected server error
   * - 504
     - Gateway Timeout
     - Request timed out

Standard Error Response Format
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: json

   {
       "success": false,
       "message": "Error description",
       "error": "Detailed error message",
       "errors": {
           "field_name": ["Validation error message"]
       }
   }

**Example - Validation Error (422 Unprocessable Entity):**

.. code-block:: json

   {
       "success": false,
       "message": "Validation failed",
       "errors": {
           "email": ["The email has already been taken."],
           "password": ["The password must be at least 8 characters."]
       }
   }

**Example - Unauthorized (401 Unauthorized):**

.. code-block:: json

   {
       "success": false,
       "message": "Unauthenticated. Please login."
   }

**Example - Rate Limit Exceeded (429 Too Many Requests):**

.. code-block:: json

   {
       "success": false,
       "message": "Too many requests. Please try again later.",
       "retry_after": 60
   }

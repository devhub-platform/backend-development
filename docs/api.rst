API Reference
=============

.. contents:: Table of Contents
   :depth: 2
   :local:

Overview
--------

**Base URL:** ``http://devhub.eu-north-1.elasticbeanstalk.com/api/v1``

**Content-Type:** ``application/json``

**Authentication:** JWT Bearer Token

.. code-block:: text

   Authorization: Bearer <your-jwt-token>

**Rate Limits:**

- Public endpoints: 15 requests/minute
- Protected endpoints: 25 requests/minute

.. note::

   For detailed request/response examples for each endpoint, see :doc:`api-examples`.

----

Authentication
--------------

Login
~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /login``
   * - **Auth Required**
     - No
   * - **Description**
     - Authenticate user and receive JWT token

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "password": "your-password"
   }

**Response (200):**

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
               "avatar": "https://cloudinary.com/avatar.jpg"
           },
           "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
           "token_type": "bearer",
           "expires_in": 3600
       }
   }

Register
~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /register``
   * - **Auth Required**
     - No
   * - **Description**
     - Create new user account

**Request Body:**

.. code-block:: json

   {
       "name": "John Doe",
       "username": "johndoe",
       "email": "john@example.com",
       "password": "SecurePass123!",
       "password_confirmation": "SecurePass123!"
   }

**Response (201):**

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
           "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
       }
   }

Social Login
~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/auth/google/login``
     - POST
     - Initiate Google OAuth
   * - ``/auth/google/callback``
     - GET
     - Google OAuth callback
   * - ``/auth/github/login``
     - POST
     - Initiate GitHub OAuth
   * - ``/auth/github/callback``
     - GET
     - GitHub OAuth callback

Email Verification
~~~~~~~~~~~~~~~~~~

Send OTP
""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /email/send-otp``
   * - **Auth Required**
     - No

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "OTP sent successfully to your email"
   }

Verify OTP
""""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /email/verify-otp``
   * - **Auth Required**
     - No

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "otp": "123456"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Email verified successfully"
   }

Check Verification Status
"""""""""""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /email/is-verified``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "is_verified": true,
           "verified_at": "2026-02-17T10:30:00Z"
       }
   }

Password Reset
~~~~~~~~~~~~~~

Forgot Password
"""""""""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /password/forgot``
   * - **Auth Required**
     - No

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Password reset OTP sent to your email"
   }

Verify Reset OTP
""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /password/verify-otp``
   * - **Auth Required**
     - No

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "otp": "123456"
   }

Reset Password
""""""""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /password/reset``
   * - **Auth Required**
     - No

**Request Body:**

.. code-block:: json

   {
       "email": "user@example.com",
       "otp": "123456",
       "password": "NewSecurePass123!",
       "password_confirmation": "NewSecurePass123!"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Password reset successfully"
   }

Token Management
~~~~~~~~~~~~~~~~

Logout
""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /logout``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Successfully logged out"
   }

Refresh Token
"""""""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /refresh``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9-NEW...",
           "token_type": "bearer",
           "expires_in": 3600
       }
   }

Get Current User
""""""""""""""""

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /me``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "id": 1,
           "name": "John Doe",
           "username": "johndoe",
           "email": "user@example.com",
           "avatar": "https://cloudinary.com/avatar.jpg",
           "cover_image": "https://cloudinary.com/cover.jpg",
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

List Posts
~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /posts``
   * - **Auth Required**
     - No
   * - **Query Params**
     - ``page``, ``per_page``

**Response (200):**

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
                   "avatar": "https://cloudinary.com/avatar.jpg"
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

Create Post
~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /posts``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "title": "My New Article",
       "content": "This is the content of my article...",
       "status": "published",
       "tags": ["javascript", "react"]
   }

**Response (201):**

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

Get Post
~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /posts/{id}``
   * - **Auth Required**
     - No

**Response (200):**

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
               "avatar": "https://cloudinary.com/avatar.jpg"
           },
           "tags": [
               {"id": 1, "name": "laravel", "slug": "laravel"},
               {"id": 2, "name": "php", "slug": "php"}
           ]
       }
   }

Update Post
~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``PUT /posts/{id}`` or ``PATCH /posts/{id}``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "title": "Updated Title",
       "content": "Updated content...",
       "status": "published"
   }

**Response (200):**

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

Delete Post
~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``DELETE /posts/{id}``
   * - **Auth Required**
     - Yes
   * - **Description**
     - Soft delete (moves to trash)

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Post deleted successfully"
   }

Force Delete Post
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``DELETE /posts/{id}/force``
   * - **Auth Required**
     - Yes
   * - **Description**
     - Permanently delete

Restore Post
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /posts/{id}/restore``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Post restored successfully"
   }

Post Queries
~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/user/posts``
     - GET
     - Get current user's posts
   * - ``/posts/recent``
     - GET
     - Get recent posts
   * - ``/posts/top-views``
     - GET
     - Get top viewed posts
   * - ``/posts/drafts``
     - GET
     - Get user's draft posts
   * - ``/posts/archives``
     - GET
     - Get archived/trashed posts
   * - ``/posts/viewed/recent``
     - GET
     - Get recently viewed posts
   * - ``/posts/viewed/clear``
     - DELETE
     - Clear viewed history

Post Tags
~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{id}/tags``
     - GET
     - Get post tags (detailed)
   * - ``/posts/{id}/tags-list``
     - GET
     - Get post tags (list)

Report Post
~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /posts/{id}/report``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "reason": "spam",
       "description": "This post contains spam content"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Post reported successfully"
   }

Get Report Reasons
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /posts/report/reasons``
   * - **Auth Required**
     - No

----

Comments
--------

Create Comment
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /posts/{postId}/comments``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "content": "Great article! Very helpful for beginners."
   }

**Response (201):**

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
               "avatar": "https://cloudinary.com/avatar.jpg"
           }
       }
   }

Reply to Comment
~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /posts/{postId}/comments/{commentId}/reply``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "content": "Thank you for your feedback!"
   }

Get Comments
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /posts/{postId}/comments``
   * - **Auth Required**
     - No

**Response (200):**

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
                   "avatar": "https://cloudinary.com/avatar.jpg"
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

Comment Endpoints
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{postId}/comments/count``
     - GET
     - Get comment count
   * - ``/users/{userId}/comments``
     - GET
     - Get comments by user
   * - ``/comments/{id}/replies``
     - GET
     - Get replies to comment
   * - ``/comments/{id}/thread``
     - GET
     - Get full thread
   * - ``/comments/{id}/pin``
     - POST
     - Pin comment
   * - ``/comments/{id}/unpin``
     - POST
     - Unpin comment
   * - ``/comments/{id}/force``
     - DELETE
     - Delete permanently
   * - ``/my/comments``
     - GET
     - Get my comments
   * - ``/my/comments/stats``
     - GET
     - Get my stats

Comment Reactions
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/comments/{id}/react``
     - POST
     - React to comment
   * - ``/comments/{id}/remove-react``
     - DELETE
     - Remove reaction
   * - ``/comments/{id}/my-reaction``
     - GET
     - Get my reaction
   * - ``/comments/{id}/reactions``
     - GET
     - Get all reactions

----

Reactions
---------

React to Post
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /posts/{id}/react``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "reaction": "love"
   }

**Available Reactions:** ``like``, ``love``, ``clap``, ``insightful``, ``celebrate``

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Reaction added successfully",
       "data": {
           "reaction": "love",
           "total_reactions": 26
       }
   }

Get Reaction Counts
~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /posts/{id}/reactions-count``
   * - **Auth Required**
     - No

**Response (200):**

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

Reaction Endpoints
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{id}/remove-react``
     - DELETE
     - Remove reaction
   * - ``/posts/{id}/my-reaction``
     - GET
     - Get my reaction
   * - ``/posts/{id}/reactors``
     - GET
     - Get list of reactors
   * - ``/user/posts/total-reactions``
     - GET
     - Get total reactions on my posts

----

Users
-----

List Users
~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /users``
   * - **Auth Required**
     - No
   * - **Query Params**
     - ``page``, ``per_page``

**Response (200):**

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

Get User
~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /users/{id}``
   * - **Auth Required**
     - No

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "id": 1,
           "name": "John Doe",
           "username": "johndoe",
           "email": "john@example.com",
           "avatar": "https://cloudinary.com/avatar.jpg",
           "cover_image": "https://cloudinary.com/cover.jpg",
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

User Endpoints
~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/users/recommended``
     - GET
     - Get recommended users
   * - ``/users/{id}/similar-skills``
     - GET
     - Get users with similar skills
   * - ``/users/{id}/posts``
     - GET
     - Get user's posts
   * - ``/users/{id}/comments``
     - GET
     - Get user's comments
   * - ``/users/{id}/tags``
     - GET
     - Get user's followed tags
   * - ``/users/{id}/followers``
     - GET
     - Get user's followers
   * - ``/users/{id}/followers/count``
     - GET
     - Get followers count
   * - ``/users/{id}/following``
     - GET
     - Get users being followed
   * - ``/users/{id}/mutual-followers``
     - GET
     - Get mutual followers
   * - ``/users/{id}/mutual-following``
     - GET
     - Check mutual following
   * - ``/users/{id}/follow-stats/count``
     - GET
     - Get follow statistics
   * - ``/users/{username}/status``
     - GET
     - Get user's status

----

Profile
-------

Get Profile
~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /profile``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "id": 1,
           "name": "John Doe",
           "username": "johndoe",
           "email": "john@example.com",
           "avatar": "https://cloudinary.com/avatar.jpg",
           "cover_image": "https://cloudinary.com/cover.jpg",
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

Update Profile
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``PATCH /profile``
   * - **Auth Required**
     - Yes

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

**Response (200):**

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

Upload Avatar
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /profile/upload/avatar``
   * - **Auth Required**
     - Yes
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
           "avatar": "https://cloudinary.com/new-avatar.jpg"
       }
   }

Upload Cover Image
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /profile/upload/cover-image``
   * - **Auth Required**
     - Yes
   * - **Content-Type**
     - ``multipart/form-data``

**Request Body:**

.. code-block:: text

   cover_image: [binary image file]

Profile Endpoints
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/profile/details``
     - GET
     - Get detailed profile info
   * - ``/profile/activity``
     - GET
     - Get my activity stats
   * - ``/profile/user/posts``
     - GET
     - Get my posts
   * - ``/profile/user/comments``
     - GET
     - Get my comments
   * - ``/profile/user/tags``
     - GET
     - Get my tags

----

Followers
---------

Follow User
~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /users/{id}/follow``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "You are now following this user",
       "data": {
           "following": true,
           "followers_count": 151
       }
   }

Unfollow User
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /users/{id}/unfollow``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "You have unfollowed this user",
       "data": {
           "following": false,
           "followers_count": 150
       }
   }

Follower Endpoints
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/followers/suggestions``
     - GET
     - Get follow suggestions
   * - ``/followers/my-followers``
     - GET
     - Get my followers
   * - ``/followers/my-following``
     - GET
     - Get who I'm following

----

Tags
----

List Tags
~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /tags``
   * - **Auth Required**
     - No

**Response (200):**

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

Create Tag
~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /tags``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "name": "typescript"
   }

**Response (201):**

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

Tag Endpoints
~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/tags/popular``
     - GET
     - Get popular tags
   * - ``/posts/{postId}/tags``
     - POST
     - Attach tags to post
   * - ``/posts/{postId}/tags/{tagId}``
     - DELETE
     - Detach tag from post
   * - ``/tags/{id}/follow``
     - POST
     - Follow a tag
   * - ``/tags/{id}/unfollow``
     - DELETE
     - Unfollow a tag
   * - ``/tags/{id}/followers``
     - GET
     - Get tag followers

----

Search
------

Search Posts
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /search/posts``
   * - **Auth Required**
     - No
   * - **Query Params**
     - ``q``, ``page``, ``per_page``

**Example:** ``GET /search/posts?q=laravel&page=1&per_page=15``

**Response (200):**

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

Search Endpoints
~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/search/users``
     - GET
     - Search users by username
   * - ``/search/tags``
     - GET
     - Search tags
   * - ``/search/histories``
     - GET
     - Get search history
   * - ``/search/clear``
     - DELETE
     - Clear search history

----

Notifications
-------------

Get All Notifications
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /notifications/all``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": [
           {
               "id": "uuid-notification-1",
               "type": "comment",
               "message": "Jane Smith commented on your post",
               "data": {
                   "post_id": 1,
                   "comment_id": 45,
                   "user": {
                       "id": 5,
                       "name": "Jane Smith",
                       "avatar": "https://cloudinary.com/avatar.jpg"
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

Notification Endpoints
~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/notifications/comments``
     - GET
     - Get comment notifications
   * - ``/notifications/reacts``
     - GET
     - Get reaction notifications
   * - ``/notifications/new-followers``
     - GET
     - Get follower notifications
   * - ``/notifications/post-created``
     - GET
     - Get new post notifications
   * - ``/notifications/mention``
     - GET
     - Get mention notifications
   * - ``/notifications/mark-as-read``
     - POST
     - Mark all as read
   * - ``/notifications/{id}/mark-as-read``
     - POST
     - Mark single as read
   * - ``/notifications/clear``
     - DELETE
     - Clear all notifications
   * - ``/notifications/followers/clear``
     - DELETE
     - Clear follower notifications
   * - ``/notifications/preferences``
     - GET
     - Get preferences
   * - ``/notifications/preferences``
     - PUT
     - Update preferences
   * - ``/notifications/preferences/{type}/toggle``
     - PATCH
     - Toggle preference

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

Create Reading List
~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /reading-lists``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "title": "React Best Practices",
       "description": "Collection of React articles"
   }

**Response (201):**

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

Reading List Endpoints
~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/reading-lists/{id}``
     - GET
     - Get single reading list
   * - ``/reading-lists/{id}``
     - PATCH
     - Update reading list
   * - ``/reading-lists/{id}``
     - DELETE
     - Delete reading list
   * - ``/reading-lists/{id}/add-post/{postId}``
     - POST
     - Add post to list
   * - ``/reading-lists/{id}/remove-post/{postId}``
     - DELETE
     - Remove post from list
   * - ``/reading-lists/{id}/move-post/{postId}``
     - POST
     - Move post to another list
   * - ``/reading-lists/{id}/add-note/{postId}``
     - POST
     - Add note to post
   * - ``/reading-lists/{id}/delete-note/{postId}``
     - DELETE
     - Delete note
   * - ``/reading-lists/{id}/show-notes/{postId}``
     - GET
     - Show notes for post
   * - ``/reading-lists/{id}/duplicate``
     - POST
     - Duplicate reading list

----

Saved Posts
-----------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/saved-posts``
     - GET
     - Get all saved posts
   * - ``/saved-posts/{postId}``
     - POST
     - Save a post
   * - ``/saved-posts/{postId}``
     - DELETE
     - Unsave a post

**GET /saved-posts Response:**

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

Get Runtimes
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /code/runtimes``
   * - **Auth Required**
     - No

**Response (200):**

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

   * - **Endpoint**
     - ``POST /code/execute``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "language": "python",
       "version": "3.10.0",
       "code": "print('Hello, World!')",
       "stdin": "",
       "timeout": 30
   }

**Response (200):**

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

Code Editor Endpoints
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/code/search-runtimes``
     - GET
     - Search runtimes
   * - ``/code/languages``
     - GET
     - Get supported languages

**Supported Languages (50+):** Python, JavaScript, TypeScript, Java, C, C++, C#, Go, Rust, Ruby, PHP, Swift, Kotlin, Scala, R, Perl, Lua, Haskell, Elixir, and more.

----

AI Features
-----------

Summarize Post
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /ai/summarize/post/{postId}``
   * - **Auth Required**
     - Yes

**Response (200):**

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

Translate Post
~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /ai/translate/post/{postId}``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "target_language": "es"
   }

**Response (200):**

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

Analyze Post
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /ai/analyze/post/{postId}``
   * - **Auth Required**
     - Yes

**Response (200):**

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

   * - **Endpoint**
     - ``POST /ai/question/post/{postId}``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "question": "What are the main benefits mentioned in this article?"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "data": {
           "question": "What are the main benefits mentioned in this article?",
           "answer": "The article highlights several key benefits...",
           "confidence": 0.95
       }
   }

Generate Content
~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /ai/generate/content``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "prompt": "Write an introduction about React hooks for beginners",
       "max_length": 300,
       "tone": "educational"
   }

AI Endpoints
~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/ai/summarize/llama/post/{postId}``
     - POST
     - Summarize using LLama
   * - ``/ai/summarize/post/languages``
     - GET
     - Get supported languages

----

AI Chat
-------

Send Message
~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /ai-chat/send``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "message": "How do I create a REST API in Laravel?",
       "session_id": "550e8400-e29b-41d4-a716-446655440000",
       "model": "llama"
   }

**Response (200):**

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

Get AI Models
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /ai-chat/models``
   * - **Auth Required**
     - Yes

**Response (200):**

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

AI Chat Endpoints
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/ai-chat/attachments/upload``
     - POST
     - Upload attachment
   * - ``/ai-chat/history/sessions``
     - GET
     - List all sessions
   * - ``/ai-chat/history/sessions/create``
     - POST
     - Create new session
   * - ``/ai-chat/history/sessions/{id}``
     - GET
     - Get session details
   * - ``/ai-chat/history/sessions/{id}``
     - DELETE
     - Delete session
   * - ``/ai-chat/history/sessions/{id}/pin``
     - POST
     - Pin session
   * - ``/ai-chat/history/sessions/{id}/unpin``
     - POST
     - Unpin session
   * - ``/ai-chat/history/sessions/{id}/close``
     - POST
     - Close session
   * - ``/ai-chat/history/sessions/{id}/activate``
     - POST
     - Activate session
   * - ``/ai-chat/history/sessions/{id}/title``
     - PUT
     - Update session title

----

User Status
-----------

Get My Status
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``GET /user/statuses``
   * - **Auth Required**
     - Yes

**Response (200):**

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

Create Status
~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /user/statuses``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "status": "In a meeting",
       "emoji": ":calendar:",
       "expires_at": "2026-02-17T18:00:00Z"
   }

Status Endpoints
~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/user/statuses``
     - PATCH
     - Update status
   * - ``/user/statuses``
     - DELETE
     - Delete status
   * - ``/user/statuses/set-busy``
     - POST
     - Set busy status
   * - ``/user/statuses/set-available``
     - POST
     - Set available status
   * - ``/user/statuses/clear-expired``
     - POST
     - Clear expired statuses

----

Reports & Blocking
------------------

Block User
~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /reports/block/{userId}``
   * - **Auth Required**
     - Yes

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "User blocked successfully"
   }

Report User/Content
~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``POST /reports/report/{targetId}``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "reason": "harassment",
       "description": "This user has been sending inappropriate messages"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Report submitted successfully",
       "data": {
           "report_id": "rpt-123",
           "status": "pending"
       }
   }

Reports Endpoints
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/reports/unblock/{userId}``
     - POST
     - Unblock user
   * - ``/reports/blocked-users``
     - GET
     - Get blocked users list
   * - ``/reports/reasons``
     - GET
     - Get report reasons

----

Settings
--------

Update Password
~~~~~~~~~~~~~~~

.. list-table::
   :widths: 20 80

   * - **Endpoint**
     - ``PATCH /settings/update-password``
   * - **Auth Required**
     - Yes

**Request Body:**

.. code-block:: json

   {
       "current_password": "OldPass123!",
       "password": "NewSecurePass456!",
       "password_confirmation": "NewSecurePass456!"
   }

**Response (200):**

.. code-block:: json

   {
       "success": true,
       "message": "Password updated successfully"
   }

Settings Endpoints
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/settings/social-accounts``
     - POST
     - Add social accounts
   * - ``/settings/soft/delete-account``
     - DELETE
     - Soft delete account
   * - ``/settings/force/delete-account``
     - DELETE
     - Permanently delete account
   * - ``/settings/alt-email/send-otp``
     - POST
     - Add alternative email
   * - ``/settings/alt-email/verify-otp``
     - POST
     - Verify alternative email
   * - ``/settings/alt-email/remove``
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
     - Request successful
   * - 201
     - Created
     - Resource created successfully
   * - 400
     - Bad Request
     - Invalid request format
   * - 401
     - Unauthorized
     - Authentication required or failed
   * - 403
     - Forbidden
     - Access denied
   * - 404
     - Not Found
     - Resource not found
   * - 409
     - Conflict
     - Resource conflict
   * - 422
     - Unprocessable Entity
     - Validation error
   * - 429
     - Too Many Requests
     - Rate limit exceeded
   * - 500
     - Internal Server Error
     - Server error
   * - 504
     - Gateway Timeout
     - Request timeout

Error Response Format
~~~~~~~~~~~~~~~~~~~~~

.. code-block:: json

   {
       "success": false,
       "message": "Error description",
       "error": "Detailed error message",
       "errors": {
           "field_name": ["Validation error message"]
       }
   }

**Example - Validation Error (422):**

.. code-block:: json

   {
       "success": false,
       "message": "Validation failed",
       "errors": {
           "email": ["The email has already been taken."],
           "password": ["The password must be at least 8 characters."]
       }
   }

**Example - Unauthorized (401):**

.. code-block:: json

   {
       "success": false,
       "message": "Unauthenticated. Please login."
   }

**Example - Rate Limit (429):**

.. code-block:: json

   {
       "success": false,
       "message": "Too many requests. Please try again later.",
       "retry_after": 60
   }

API Examples
============

This section provides detailed request and response examples for all API endpoints.
Use these examples to understand the expected format for API calls.

Base URL: ``https://devhub.eu-north-1.elasticbeanstalk.com/api/v1``

.. contents:: Contents
   :depth: 2
   :local:

----

Authentication Examples
-----------------------

POST /login
~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/login HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Content-Type: application/json

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
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
                "email_verified_at": "2026-01-15T10:00:00Z"
            },
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
            "token_type": "bearer",
            "expires_in": 3600
        }
    }

**Error Response (401 Unauthorized):**

.. code-block:: json

    {
        "success": false,
        "message": "Invalid credentials"
    }

POST /register
~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/register HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Content-Type: application/json

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

**Error Response (422 Validation Error):**

.. code-block:: json

    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "email": ["The email has already been taken."],
            "username": ["The username has already been taken."]
        }
    }

POST /email/send-otp
~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/email/send-otp HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Content-Type: application/json

    {
        "email": "user@example.com"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "OTP sent successfully to your email"
    }

POST /email/verify-otp
~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/email/verify-otp HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Content-Type: application/json

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

POST /password/forgot
~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/password/forgot HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Content-Type: application/json

    {
        "email": "user@example.com"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Password reset OTP sent to your email"
    }

POST /password/reset
~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/password/reset HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Content-Type: application/json

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

POST /logout
~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/logout HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Successfully logged out"
    }

POST /refresh
~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/refresh HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": {
            "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9-NEW-TOKEN...",
            "token_type": "bearer",
            "expires_in": 3600
        }
    }

GET /me
~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/me HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

Posts Examples
--------------

GET /posts
~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/posts?page=1&per_page=15 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

POST /posts
~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/posts HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

GET /posts/{id}
~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/posts/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

PUT /posts/{id}
~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    PUT /api/v1/posts/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

DELETE /posts/{id}
~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    DELETE /api/v1/posts/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Post deleted successfully"
    }

POST /posts/{id}/restore
~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/posts/1/restore HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Post restored successfully"
    }

POST /posts/{id}/report
~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/posts/1/report HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

----

Comments Examples
-----------------

POST /posts/{postId}/comments
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/posts/1/comments HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

POST /posts/{postId}/comments/{commentId}/reply
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/posts/1/comments/45/reply HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "content": "Thank you for your feedback!"
    }

**Success Response (201 Created):**

.. code-block:: json

    {
        "success": true,
        "message": "Reply created successfully",
        "data": {
            "id": 46,
            "content": "Thank you for your feedback!",
            "post_id": 1,
            "user_id": 1,
            "parent_id": 45,
            "is_pinned": false,
            "created_at": "2026-02-17T15:35:00Z"
        }
    }

GET /posts/{postId}/comments
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/posts/1/comments HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

POST /comments/{id}/react
~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/comments/45/react HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "reaction": "like"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Reaction added successfully",
        "data": {
            "reaction": "like",
            "reactions_count": 6
        }
    }

POST /comments/{id}/pin
~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/comments/45/pin HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Comment pinned successfully"
    }

----

Reactions Examples
------------------

POST /posts/{id}/react
~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/posts/1/react HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "reaction": "love"
    }

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

**Available Reactions:** ``like``, ``love``, ``clap``, ``insightful``, ``celebrate``

GET /posts/{id}/reactions-count
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/posts/1/reactions-count HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

GET /posts/{id}/reactors
~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/posts/1/reactors HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "user": {
                    "id": 5,
                    "name": "Jane Smith",
                    "username": "janesmith",
                    "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
                },
                "reaction": "love",
                "reacted_at": "2026-02-17T14:00:00Z"
            }
        ]
    }

DELETE /posts/{id}/remove-react
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    DELETE /api/v1/posts/1/remove-react HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Reaction removed successfully"
    }

----

Users Examples
--------------

GET /users
~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/users?page=1&per_page=15 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
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

GET /users/{id}
~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/users/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

GET /users/recommended
~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/users/recommended HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 5,
                "name": "Jane Smith",
                "username": "janesmith",
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
                "bio": "React Developer",
                "mutual_followers": 3,
                "reason": "Similar skills"
            }
        ]
    }

----

Profile Examples
----------------

GET /profile
~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/profile HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

PATCH /profile
~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    PATCH /api/v1/profile HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "name": "John Updated",
        "bio": "Senior Full-stack developer",
        "location": "New York, NY"
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

POST /profile/upload/avatar
~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/profile/upload/avatar HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: multipart/form-data

    avatar: [binary image file]

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Avatar uploaded successfully",
        "data": {
            "avatar": "https://res.cloudinary.com/devhub/image/new-avatar.jpg"
        }
    }

POST /profile/upload/cover-image
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/profile/upload/cover-image HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: multipart/form-data

    cover_image: [binary image file]

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Cover image uploaded successfully",
        "data": {
            "cover_image": "https://res.cloudinary.com/devhub/image/new-cover.jpg"
        }
    }

GET /profile/activity
~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/profile/activity HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": {
            "posts_count": 25,
            "comments_count": 150,
            "reactions_received": 500,
            "reactions_given": 200,
            "followers_gained_this_month": 15,
            "posts_this_month": 5,
            "most_active_day": "Monday"
        }
    }

----

Followers Examples
------------------

POST /users/{id}/follow
~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/users/5/follow HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

POST /users/{id}/unfollow
~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/users/5/unfollow HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

GET /followers/suggestions
~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/followers/suggestions HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 10,
                "name": "Alex Johnson",
                "username": "alexj",
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
                "bio": "Backend Developer",
                "mutual_followers_count": 5,
                "reason": "Followed by people you follow"
            }
        ]
    }

GET /followers/my-followers
~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/followers/my-followers HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 5,
                "name": "Jane Smith",
                "username": "janesmith",
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
                "bio": "React Developer",
                "is_following_back": true,
                "followed_at": "2026-02-01T10:00:00Z"
            }
        ],
        "meta": {
            "total": 150
        }
    }

----

Tags Examples
-------------

GET /tags
~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/tags HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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
            },
            {
                "id": 2,
                "name": "javascript",
                "slug": "javascript",
                "posts_count": 200,
                "followers_count": 750
            }
        ]
    }

GET /tags/popular
~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/tags/popular HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 1,
                "name": "javascript",
                "posts_count": 500,
                "trend": "up"
            },
            {
                "id": 2,
                "name": "python",
                "posts_count": 450,
                "trend": "stable"
            }
        ]
    }

POST /tags
~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/tags HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

POST /posts/{postId}/tags
~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/posts/1/tags HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "tags": ["laravel", "php", "api"]
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Tags attached successfully",
        "data": {
            "tags": [
                {"id": 1, "name": "laravel"},
                {"id": 2, "name": "php"},
                {"id": 15, "name": "api"}
            ]
        }
    }

POST /tags/{id}/follow
~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/tags/1/follow HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "You are now following this tag"
    }

----

Search Examples
---------------

GET /search/posts
~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/search/posts?q=laravel&page=1&per_page=15 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

GET /search/users
~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/search/users?q=john HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
                "bio": "Full-stack developer"
            }
        ]
    }

GET /search/tags
~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/search/tags?q=java HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {"id": 5, "name": "java", "posts_count": 150},
            {"id": 6, "name": "javascript", "posts_count": 500}
        ]
    }

GET /search/histories
~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/search/histories HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 1,
                "query": "laravel",
                "type": "posts",
                "searched_at": "2026-02-17T10:00:00Z"
            },
            {
                "id": 2,
                "query": "react hooks",
                "type": "posts",
                "searched_at": "2026-02-16T15:30:00Z"
            }
        ]
    }

----

Notifications Examples
----------------------

GET /notifications/all
~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/notifications/all HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

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
                        "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
                    }
                },
                "read_at": null,
                "created_at": "2026-02-17T15:30:00Z"
            },
            {
                "id": "uuid-notification-2",
                "type": "follow",
                "message": "Alex Johnson started following you",
                "data": {
                    "user": {
                        "id": 10,
                        "name": "Alex Johnson",
                        "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg"
                    }
                },
                "read_at": "2026-02-17T14:00:00Z",
                "created_at": "2026-02-17T13:00:00Z"
            }
        ],
        "meta": {
            "unread_count": 5,
            "total": 50
        }
    }

POST /notifications/mark-as-read
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/notifications/mark-as-read HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "All notifications marked as read"
    }

GET /notifications/preferences
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/notifications/preferences HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": {
            "email_notifications": true,
            "push_notifications": true,
            "comment_notifications": true,
            "reaction_notifications": true,
            "follower_notifications": true,
            "mention_notifications": true,
            "post_from_following": true
        }
    }

PUT /notifications/preferences
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    PUT /api/v1/notifications/preferences HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "email_notifications": false,
        "comment_notifications": true,
        "reaction_notifications": false
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Preferences updated successfully"
    }

----

Reading Lists Examples
----------------------

GET /reading-lists/lists/posts
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/reading-lists/lists/posts HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

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

POST /reading-lists
~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/reading-lists HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

POST /reading-lists/{id}/add-post/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/reading-lists/5/add-post/25 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Post added to reading list",
        "data": {
            "post_id": 25,
            "reading_list_id": 5,
            "added_at": "2026-02-17T16:05:00Z"
        }
    }

POST /reading-lists/{id}/add-note/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/reading-lists/5/add-note/25 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "note": "Check the section about hooks"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Note added successfully",
        "data": {
            "note": "Check the section about hooks",
            "created_at": "2026-02-17T16:10:00Z"
        }
    }

----

Saved Posts Examples
--------------------

GET /saved-posts
~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/saved-posts HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

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

POST /saved-posts/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/saved-posts/25 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Post saved successfully"
    }

DELETE /saved-posts/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    DELETE /api/v1/saved-posts/25 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Post removed from saved"
    }

----

Code Editor Examples
--------------------

GET /code/runtimes
~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/code/runtimes HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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
            },
            {
                "language": "java",
                "version": "15.0.2",
                "aliases": []
            }
        ],
        "count": 50
    }

POST /code/execute
~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/code/execute HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

**Execute Code with Input (stdin):**

.. code-block:: http

    POST /api/v1/code/execute HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "language": "python",
        "version": "3.10.0",
        "code": "name = input()\nprint(f'Hello, {name}!')",
        "stdin": "John",
        "timeout": 30
    }

**Response:**

.. code-block:: json

    {
        "success": true,
        "language": "python",
        "version": "3.10.0",
        "run": {
            "stdout": "Hello, John!\n",
            "stderr": "",
            "code": 0,
            "output": "Hello, John!\n",
            "cpu_time": 35,
            "wall_time": 55
        }
    }

**Execute Code with Error:**

.. code-block:: json

    {
        "success": true,
        "language": "python",
        "version": "3.10.0",
        "run": {
            "stdout": "",
            "stderr": "NameError: name 'undefined_variable' is not defined",
            "code": 1,
            "output": "NameError: name 'undefined_variable' is not defined",
            "cpu_time": 25,
            "wall_time": 45
        }
    }

GET /code/languages
~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/code/languages HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            "python", "javascript", "typescript", "java", "c",
            "cpp", "csharp", "go", "rust", "ruby", "php",
            "swift", "kotlin", "scala", "r", "perl", "lua",
            "haskell", "elixir", "dart", "bash", "sql"
        ],
        "count": 50
    }

----

AI Examples
-----------

POST /ai/summarize/post/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/ai/summarize/post/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": {
            "original_length": 2500,
            "summary": "This article discusses the fundamentals of Laravel framework, covering routing, controllers, models, and migrations. Key points include...",
            "summary_length": 350,
            "language": "en"
        }
    }

POST /ai/translate/post/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/ai/translate/post/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

POST /ai/analyze/post/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/ai/analyze/post/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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
            "key_concepts": ["MVC", "routing", "migrations"],
            "suggestions": [
                "Consider adding code examples",
                "Include a summary section"
            ]
        }
    }

POST /ai/question/post/{postId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/ai/question/post/1 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "question": "What are the main benefits mentioned in this article?"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": {
            "question": "What are the main benefits mentioned in this article?",
            "answer": "The article highlights several key benefits: 1) Easy to learn syntax, 2) Built-in authentication, 3) Eloquent ORM for database operations...",
            "confidence": 0.95,
            "related_sections": ["Introduction", "Key Features"]
        }
    }

POST /ai/generate/content
~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/ai/generate/content HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "prompt": "Write an introduction about React hooks for beginners",
        "max_length": 300,
        "tone": "educational"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": {
            "generated_content": "React Hooks are a powerful feature introduced in React 16.8 that allow you to use state and other React features without writing a class component. They provide a more direct API to the React concepts you already know...",
            "word_count": 150,
            "tokens_used": 200
        }
    }

----

AI Chat Examples
----------------

POST /ai-chat/send
~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/ai-chat/send HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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
            "response": "To create a REST API in Laravel, follow these steps:\n\n1. **Create Routes**: Define API routes in `routes/api.php`\n2. **Create Controller**: Generate a controller using `php artisan make:controller ApiController`\n3. **Create Resource**: Use API Resources for response formatting...",
            "session_id": "550e8400-e29b-41d4-a716-446655440000",
            "model": "llama",
            "tokens_used": 350,
            "created_at": "2026-02-17T16:30:00Z"
        }
    }

GET /ai-chat/models
~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/ai-chat/models HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

POST /ai-chat/history/sessions/create
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/ai-chat/history/sessions/create HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "title": "Laravel API Discussion"
    }

**Success Response (201 Created):**

.. code-block:: json

    {
        "success": true,
        "data": {
            "session_id": "550e8400-e29b-41d4-a716-446655440001",
            "title": "Laravel API Discussion",
            "is_pinned": false,
            "is_active": true,
            "created_at": "2026-02-17T16:00:00Z"
        }
    }

GET /ai-chat/history/sessions
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/ai-chat/history/sessions HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "session_id": "550e8400-e29b-41d4-a716-446655440000",
                "title": "React Hooks Discussion",
                "is_pinned": true,
                "is_active": false,
                "messages_count": 15,
                "last_message_at": "2026-02-17T15:00:00Z",
                "created_at": "2026-02-16T10:00:00Z"
            }
        ],
        "meta": {
            "total": 10
        }
    }

----

User Status Examples
--------------------

GET /user/statuses
~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/user/statuses HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

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

POST /user/statuses
~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/user/statuses HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "status": "In a meeting",
        "emoji": ":calendar:",
        "expires_at": "2026-02-17T18:00:00Z"
    }

**Success Response (201 Created):**

.. code-block:: json

    {
        "success": true,
        "message": "Status created successfully",
        "data": {
            "id": 2,
            "status": "In a meeting",
            "emoji": ":calendar:",
            "expires_at": "2026-02-17T18:00:00Z"
        }
    }

POST /user/statuses/set-busy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/user/statuses/set-busy HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Status set to busy",
        "data": {
            "is_busy": true,
            "status": "Busy"
        }
    }

----

Reports & Blocking Examples
---------------------------

POST /reports/block/{userId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/reports/block/50 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "User blocked successfully"
    }

POST /reports/report/{targetId}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/reports/report/50 HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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
            "report_id": "rpt-123",
            "status": "pending"
        }
    }

GET /reports/blocked-users
~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/reports/blocked-users HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {
                "id": 50,
                "name": "Blocked User",
                "username": "blockeduser",
                "avatar": "https://res.cloudinary.com/devhub/image/avatar.jpg",
                "blocked_at": "2026-02-10T14:00:00Z"
            }
        ],
        "meta": {
            "total": 3
        }
    }

GET /reports/reasons
~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    GET /api/v1/reports/reasons HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "data": [
            {"id": 1, "reason": "spam", "label": "Spam"},
            {"id": 2, "reason": "harassment", "label": "Harassment"},
            {"id": 3, "reason": "hate_speech", "label": "Hate Speech"},
            {"id": 4, "reason": "misinformation", "label": "Misinformation"},
            {"id": 5, "reason": "inappropriate", "label": "Inappropriate Content"},
            {"id": 6, "reason": "other", "label": "Other"}
        ]
    }

----

Settings Examples
-----------------

PATCH /settings/update-password
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    PATCH /api/v1/settings/update-password HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

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

**Error Response (422 Validation Error):**

.. code-block:: json

    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "current_password": ["The current password is incorrect."]
        }
    }

POST /settings/alt-email/send-otp
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/settings/alt-email/send-otp HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "alt_email": "john.backup@example.com"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "OTP sent to alternative email"
    }

POST /settings/alt-email/verify-otp
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    POST /api/v1/settings/alt-email/verify-otp HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "alt_email": "john.backup@example.com",
        "otp": "123456"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Alternative email verified successfully"
    }

DELETE /settings/soft/delete-account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    DELETE /api/v1/settings/soft/delete-account HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Account deactivated. You can reactivate within 30 days."
    }

DELETE /settings/force/delete-account
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

**Request:**

.. code-block:: http

    DELETE /api/v1/settings/force/delete-account HTTP/1.1
    Host: devhub.eu-north-1.elasticbeanstalk.com
    Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
    Content-Type: application/json

    {
        "password": "CurrentPassword123!",
        "confirmation": "DELETE"
    }

**Success Response (200 OK):**

.. code-block:: json

    {
        "success": true,
        "message": "Account permanently deleted"
    }

----

Error Responses
---------------

401 Unauthorized
~~~~~~~~~~~~~~~~

.. code-block:: json

    {
        "success": false,
        "message": "Unauthenticated. Please login."
    }

403 Forbidden
~~~~~~~~~~~~~

.. code-block:: json

    {
        "success": false,
        "message": "You do not have permission to perform this action."
    }

404 Not Found
~~~~~~~~~~~~~

.. code-block:: json

    {
        "success": false,
        "message": "Resource not found."
    }

422 Validation Error
~~~~~~~~~~~~~~~~~~~~

.. code-block:: json

    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "field_name": ["Error message for this field"]
        }
    }

429 Too Many Requests
~~~~~~~~~~~~~~~~~~~~~

.. code-block:: json

    {
        "success": false,
        "message": "Too many requests. Please try again later.",
        "retry_after": 60
    }

500 Internal Server Error
~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: json

    {
        "success": false,
        "message": "An unexpected error occurred. Please try again later."
    }


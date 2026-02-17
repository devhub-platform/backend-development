API Reference
=============

Base URL: ``http://devhub.eu-north-1.elasticbeanstalk.com/api/v1``

All protected endpoints require JWT token in the Authorization header:

.. code-block:: text

   Authorization: Bearer <your-jwt-token>

.. note::

   For detailed request/response examples for each endpoint, see :doc:`api-examples`.

Authentication
--------------

Public Endpoints
~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/login``
     - POST
     - User login
   * - ``/register``
     - POST
     - User registration
   * - ``/auth/google/login``
     - POST
     - Login with Google
   * - ``/auth/google/callback``
     - GET
     - Google OAuth callback
   * - ``/auth/github/login``
     - POST
     - Login with GitHub
   * - ``/auth/github/callback``
     - GET
     - GitHub OAuth callback

Email Verification
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/email/send-otp``
     - POST
     - Send verification OTP
   * - ``/email/verify-otp``
     - POST
     - Verify email with OTP
   * - ``/email/is-verified``
     - GET
     - Check verification status

Password Reset
~~~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/password/forgot``
     - POST
     - Request password reset
   * - ``/password/verify-otp``
     - POST
     - Verify reset OTP
   * - ``/password/reset``
     - POST
     - Reset password

Protected Auth Endpoints
~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/logout``
     - POST
     - Logout user
   * - ``/refresh``
     - POST
     - Refresh JWT token
   * - ``/me``
     - GET
     - Get current user info

Posts
-----

CRUD Operations
~~~~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts``
     - GET
     - List all posts (paginated)
   * - ``/posts``
     - POST
     - Create new post
   * - ``/posts/{id}``
     - GET
     - Get single post
   * - ``/posts/{id}``
     - PUT
     - Update post
   * - ``/posts/{id}``
     - PATCH
     - Partial update post
   * - ``/posts/{id}``
     - DELETE
     - Soft delete post
   * - ``/posts/{id}/force``
     - DELETE
     - Permanently delete post
   * - ``/posts/{id}/restore``
     - POST
     - Restore deleted post

Post Queries
~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
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

Post Tags & Comments
~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
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
   * - ``/posts/{id}/comments``
     - GET
     - Get post comments

Post Reporting & Views
~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 40 10 50
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{id}/report``
     - POST
     - Report a post
   * - ``/posts/report/reasons``
     - GET
     - Get report reasons
   * - ``/posts/viewed/recent``
     - GET
     - Get recently viewed posts
   * - ``/posts/viewed/clear``
     - DELETE
     - Clear viewed posts history

Comments
--------

Create & Reply
~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{postId}/comments``
     - POST
     - Create comment on post
   * - ``/posts/{postId}/comments/{commentId}/reply``
     - POST
     - Reply to a comment

Get Comments
~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{postId}/comments``
     - GET
     - Get comments for a post
   * - ``/posts/{postId}/comments/count``
     - GET
     - Get comment count for post
   * - ``/users/{userId}/comments``
     - GET
     - Get comments by user
   * - ``/comments/{id}/replies``
     - GET
     - Get replies to a comment
   * - ``/comments/{id}/thread``
     - GET
     - Get full comment thread

Comment Actions
~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/comments/{id}/pin``
     - POST
     - Pin a comment
   * - ``/comments/{id}/unpin``
     - POST
     - Unpin a comment
   * - ``/comments/{id}/force``
     - DELETE
     - Delete comment permanently

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
     - React to a comment
   * - ``/comments/{id}/remove-react``
     - DELETE
     - Remove reaction from comment
   * - ``/comments/{id}/my-reaction``
     - GET
     - Get my reaction on comment
   * - ``/comments/{id}/reactions``
     - GET
     - Get all reactions on comment

My Comments
~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/my/comments``
     - GET
     - Get my recent comments
   * - ``/my/comments/stats``
     - GET
     - Get my comment statistics

Reactions
---------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{id}/react``
     - POST
     - React to a post
   * - ``/posts/{id}/remove-react``
     - DELETE
     - Remove reaction from post
   * - ``/posts/{id}/my-reaction``
     - GET
     - Get my reaction on post
   * - ``/posts/{id}/reactions-count``
     - GET
     - Get reaction counts
   * - ``/posts/{id}/reactors``
     - GET
     - Get list of reactors
   * - ``/user/posts/total-reactions``
     - GET
     - Get total reactions on my posts

**Available Reactions:** ``like``, ``love``, ``clap``, ``insightful``, ``celebrate``

Users
-----

User Discovery
~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/users``
     - GET
     - List all users
   * - ``/users/recommended``
     - GET
     - Get recommended users
   * - ``/users/{id}``
     - GET
     - Get user profile
   * - ``/users/{id}/similar-skills``
     - GET
     - Get users with similar skills

User Content
~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/users/{id}/posts``
     - GET
     - Get user's posts
   * - ``/users/{id}/comments``
     - GET
     - Get user's comments
   * - ``/users/{id}/tags``
     - GET
     - Get user's followed tags

User Followers
~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
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
     - Get user's status by username

Profile
-------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/profile``
     - GET
     - Get my profile
   * - ``/profile``
     - PATCH
     - Update my profile
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
   * - ``/profile/upload/avatar``
     - POST
     - Upload avatar image
   * - ``/profile/upload/cover-image``
     - POST
     - Upload cover image

Followers
---------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/users/{id}/follow``
     - POST
     - Follow a user
   * - ``/users/{id}/unfollow``
     - POST
     - Unfollow a user
   * - ``/followers/suggestions``
     - GET
     - Get follow suggestions
   * - ``/followers/my-followers``
     - GET
     - Get my followers
   * - ``/followers/my-following``
     - GET
     - Get who I'm following

Tags
----

Tag Management
~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/tags``
     - GET
     - Get all tags
   * - ``/tags``
     - POST
     - Create new tag
   * - ``/tags/popular``
     - GET
     - Get popular tags

Post Tags
~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/posts/{postId}/tags``
     - POST
     - Attach tags to post
   * - ``/posts/{postId}/tags/{tagId}``
     - DELETE
     - Detach tag from post

Tag Following
~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/tags/{id}/follow``
     - POST
     - Follow a tag
   * - ``/tags/{id}/unfollow``
     - DELETE
     - Unfollow a tag
   * - ``/tags/{id}/followers``
     - GET
     - Get tag followers

Search
------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/search/posts``
     - GET
     - Search posts
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

Notifications
-------------

Get Notifications
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/notifications/all``
     - GET
     - Get all notifications
   * - ``/notifications/comments``
     - GET
     - Get comment notifications
   * - ``/notifications/reacts``
     - GET
     - Get reaction notifications
   * - ``/notifications/new-followers``
     - GET
     - Get new follower notifications
   * - ``/notifications/post-created``
     - GET
     - Get new post notifications
   * - ``/notifications/mention``
     - GET
     - Get mention notifications

Notification Actions
~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
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

Notification Preferences
~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/notifications/preferences``
     - GET
     - Get notification preferences
   * - ``/notifications/preferences``
     - PUT
     - Update all preferences
   * - ``/notifications/preferences/{type}/toggle``
     - PATCH
     - Toggle specific preference

Reading Lists
-------------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/reading-lists/lists/posts``
     - GET
     - Get all reading lists
   * - ``/reading-lists``
     - POST
     - Create reading list
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
     - Add note to post in list
   * - ``/reading-lists/{id}/delete-note/{postId}``
     - DELETE
     - Delete note from post
   * - ``/reading-lists/{id}/show-notes/{postId}``
     - GET
     - Show notes for post
   * - ``/reading-lists/{id}/duplicate``
     - POST
     - Duplicate reading list

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

Code Editor
-----------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/code/runtimes``
     - GET
     - Get available runtimes
   * - ``/code/execute``
     - POST
     - Execute code
   * - ``/code/search-runtimes``
     - GET
     - Search runtimes
   * - ``/code/languages``
     - GET
     - Get supported languages

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
       "cpu_time": 31,
       "wall_time": 51
     }
   }

AI Features
-----------

Post AI Features
~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/ai/summarize/post/{postId}``
     - POST
     - Summarize a post
   * - ``/ai/summarize/llama/post/{postId}``
     - POST
     - Summarize using LLama
   * - ``/ai/translate/post/{postId}``
     - POST
     - Translate a post
   * - ``/ai/analyze/post/{postId}``
     - POST
     - Analyze post content
   * - ``/ai/question/post/{postId}``
     - POST
     - Ask question about post
   * - ``/ai/generate/content``
     - POST
     - Generate content
   * - ``/ai/summarize/post/languages``
     - GET
     - Get supported languages

AI Chat
-------

Chat
~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/ai-chat/send``
     - POST
     - Send chat message
   * - ``/ai-chat/models``
     - GET
     - Get available AI models
   * - ``/ai-chat/attachments/upload``
     - POST
     - Upload attachment

Chat History
~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
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

User Status
-----------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/user/statuses``
     - GET
     - Get my statuses
   * - ``/user/statuses``
     - POST
     - Create status
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

Reports & Blocking
------------------

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/reports/block/{userId}``
     - POST
     - Block a user
   * - ``/reports/unblock/{userId}``
     - POST
     - Unblock a user
   * - ``/reports/report/{targetId}``
     - POST
     - Report user/content
   * - ``/reports/blocked-users``
     - GET
     - Get blocked users list
   * - ``/reports/reasons``
     - GET
     - Get report reasons

Settings
--------

Password & Account
~~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/settings/update-password``
     - PATCH
     - Update password
   * - ``/settings/social-accounts``
     - POST
     - Add social accounts
   * - ``/settings/soft/delete-account``
     - DELETE
     - Soft delete account
   * - ``/settings/force/delete-account``
     - DELETE
     - Permanently delete account

Alternative Email
~~~~~~~~~~~~~~~~~

.. list-table::
   :widths: 50 10 40
   :header-rows: 1

   * - Endpoint
     - Method
     - Description
   * - ``/settings/alt-email/send-otp``
     - POST
     - Add alternative email
   * - ``/settings/alt-email/verify-otp``
     - POST
     - Verify alternative email
   * - ``/settings/alt-email/remove``
     - DELETE
     - Remove alternative email

Error Handling
--------------

HTTP Status Codes
~~~~~~~~~~~~~~~~~

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
     - Bad Request
   * - 401
     - Unauthorized
   * - 403
     - Forbidden
   * - 404
     - Not Found
   * - 409
     - Conflict
   * - 422
     - Validation Error
   * - 429
     - Too Many Requests
   * - 500
     - Internal Server Error
   * - 504
     - Gateway Timeout

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

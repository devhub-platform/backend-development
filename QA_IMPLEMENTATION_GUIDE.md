# Q&A Features Implementation - Complete Guide

## Overview
A comprehensive question-and-answer system has been implemented for the DevHub platform, allowing users to publish questions, receive answers from the community, and interact through voting and acceptance mechanisms.

## Implementation Summary

### 1. Database Models & Migrations

#### Tables Created:
- **questions** - Stores Q&A questions with metadata
- **answers** - Stores answers to questions
- **question_votes** - Tracks upvotes/downvotes on questions
- **answer_votes** - Tracks upvotes/downvotes on answers

#### Migration Files:
```
database/migrations/2026_02_20_100000_create_questions_table.php
database/migrations/2026_02_20_100001_create_answers_table.php
database/migrations/2026_02_20_100002_create_question_votes_table.php
database/migrations/2026_02_20_100003_create_answer_votes_table.php
```

#### Eloquent Models:
- `app/Models/Question.php` - Main question model with relationships and scopes
- `app/Models/Answer.php` - Answer model with voting helpers
- `app/Models/QuestionVote.php` - Question voting model
- `app/Models/AnswerVote.php` - Answer voting model

**Key Features:**
- Full-text search support via Scout
- Vote scoring system (upvotes - downvotes)
- Answer acceptance tracking
- View counting for questions
- Soft deletes support

### 2. Validation & Requests

#### Form Request Classes:
```
app/Http/Requests/QuestionsRequests/
  ├── StoreQuestionRequest.php (required: title 10-200 chars, content 20-5000 chars)
  ├── UpdateQuestionRequest.php
  └── VoteQuestionRequest.php

app/Http/Requests/AnswersRequests/
  ├── StoreAnswerRequest.php (required: content 10-5000 chars)
  ├── UpdateAnswerRequest.php
  └── VoteAnswerRequest.php
```

**Validation Rules:**
- Title: min 10, max 200 characters
- Content: min 20, max 5000 characters
- Vote type: must be 'upvote' or 'downvote'
- Post ID: must exist in posts table (optional)

### 3. Authorization Policies

#### Policy Files:
```
app/Policies/QuestionPolicy.php
app/Policies/AnswerPolicy.php
```

**Authorization Rules:**
- Any authenticated user can create questions/answers
- Users can only update/delete their own questions/answers
- Only question creator can accept answers
- Users cannot vote on their own questions/answers

### 4. API Resources (JSON Responses)

```
app/Http/Resources/
  ├── QuestionResource.php
  └── AnswerResource.php
```

**Question Resource Includes:**
- ID, title, content, slug
- Resolution status, view count, answer count
- Vote scores (upvotes, downvotes, net score)
- User information
- Related post information
- Accepted answer details
- Current user's vote status

**Answer Resource Includes:**
- ID, content, acceptance status
- Helpful count, vote scores
- User information
- Question relationship
- Current user's vote status

### 5. Business Logic Services

#### Service Classes:

**QuestionService** (`app/Services/QuestionService.php`)
- `createQuestion()` - Create new question with slug generation
- `updateQuestion()` - Update question details
- `deleteQuestion()` - Soft delete question
- `getQuestions()` - Paginated with filters (sort by: recent, popular, unanswered, trending)
- `getQuestionWithAnswers()` - Get question with answers (auto-increments views)
- `searchQuestions()` - Full-text search support
- `acceptAnswer()` - Mark answer as accepted
- `unacceptAnswer()` - Remove accepted status
- `getUserQuestions()` - Get user's questions
- `getUserAnsweredQuestions()` - Get questions user answered

**AnswerService** (`app/Services/AnswerService.php`)
- `createAnswer()` - Create answer and update question answer count
- `updateAnswer()` - Update answer content
- `deleteAnswer()` - Delete and decrement question answer count
- `getQuestionAnswers()` - Get paginated answers sorted by acceptance, helpful count, then recency
- `getUserAnswers()` - Get user's answers
- `getUserAcceptedAnswers()` - Get user's accepted answers
- `getUserAnswerCount()` - Count user answers
- `getUserAcceptedAnswerCount()` - Count accepted answers

**VoteService** (`app/Services/VoteService.php`)
- `voteQuestion()` - Vote on question (toggle or change vote)
- `voteAnswer()` - Vote on answer (toggle or change vote)
- `removeQuestionVote()` - Remove vote from question
- `removeAnswerVote()` - Remove vote from answer
- `markAnswerHelpful()` - Increment helpful count
- `getQuestionVoteScore()` - Calculate net vote score
- `getAnswerVoteScore()` - Calculate net vote score

### 6. Notifications

#### Notification Classes:
```
app/Notifications/
  ├── QuestionCreatedNotification.php
  ├── NewAnswerNotification.php
  └── AnswerAcceptedNotification.php
```

**Triggers:**
- `QuestionCreatedNotification` - When question is created (can notify followers)
- `NewAnswerNotification` - When answer is posted to user's question
- `AnswerAcceptedNotification` - When user's answer is accepted

### 7. Controllers

#### QuestionController (`app/Http/Controllers/V1/QuestionController.php`)
**Endpoints:**
- `GET /questions` - List all questions with pagination & filters
- `POST /questions` - Create new question
- `GET /questions/{question}` - Get single question with answers
- `PATCH /questions/{question}` - Update question
- `DELETE /questions/{question}` - Delete question
- `POST /questions/{question}/vote` - Vote on question
- `GET /questions/user/my-questions` - Get user's questions
- `GET /questions/search` - Search questions

#### AnswerController (`app/Http/Controllers/V1/AnswerController.php`)
**Endpoints:**
- `GET /questions/{question}/answers` - List answers for question
- `POST /questions/{question}/answers` - Create answer
- `GET /questions/{question}/answers/{answer}` - Get single answer
- `PATCH /questions/{question}/answers/{answer}` - Update answer
- `DELETE /questions/{question}/answers/{answer}` - Delete answer
- `POST /questions/{question}/answers/{answer}/accept` - Accept answer
- `POST /questions/{question}/answers/{answer}/vote` - Vote on answer
- `GET /answers/user/my-answers` - Get user's answers
- `GET /answers/user/accepted-answers` - Get user's accepted answers

### 8. API Routes

**Routes registered in:** `routes/api.php`

```php
// Question routes
GET    /api/v1/questions                          // List questions
POST   /api/v1/questions                          // Create question
GET    /api/v1/questions/search                   // Search questions
GET    /api/v1/questions/user/my-questions        // User's questions
GET    /api/v1/questions/{question}               // Get question
PATCH  /api/v1/questions/{question}               // Update question
DELETE /api/v1/questions/{question}               // Delete question
POST   /api/v1/questions/{question}/vote          // Vote on question

// Answer routes
GET    /api/v1/questions/{question}/answers                      // List answers
POST   /api/v1/questions/{question}/answers                      // Create answer
GET    /api/v1/questions/{question}/answers/{answer}             // Get answer
PATCH  /api/v1/questions/{question}/answers/{answer}             // Update answer
DELETE /api/v1/questions/{question}/answers/{answer}             // Delete answer
POST   /api/v1/questions/{question}/answers/{answer}/accept      // Accept answer
POST   /api/v1/questions/{question}/answers/{answer}/vote        // Vote on answer
GET    /api/v1/answers/user/my-answers                           // User's answers
GET    /api/v1/answers/user/accepted-answers                     // User's accepted answers
```

### 9. Model Relationships

#### User Model Updates:
```php
public function questions(): HasMany
public function answers(): HasMany
```

#### Post Model Updates:
```php
public function questions(): HasMany
```

#### Question Model Relationships:
```php
public function user(): BelongsTo           // Question author
public function post(): BelongsTo          // Related post (optional)
public function answers(): HasMany         // Answers to question
public function acceptedAnswer(): BelongsTo // Accepted answer
public function votes(): HasMany           // Question votes
```

#### Answer Model Relationships:
```php
public function question(): BelongsTo      // Parent question
public function user(): BelongsTo          // Answer author
public function votes(): HasMany           // Answer votes
```

## Features

### Question Features
- ✅ Create questions (with optional post linking)
- ✅ Update own questions
- ✅ Delete own questions
- ✅ Vote on questions (upvote/downvote, toggle)
- ✅ View questions (tracked)
- ✅ Mark questions as resolved
- ✅ Full-text search on questions
- ✅ Filter by resolved status
- ✅ Filter by post
- ✅ Sort by recent, popular, unanswered, trending

### Answer Features
- ✅ Create answers to questions
- ✅ Update own answers
- ✅ Delete own answers
- ✅ Vote on answers (upvote/downvote, toggle)
- ✅ Mark answer as accepted (question owner only)
- ✅ Automatic answer count tracking
- ✅ Accepted answers sorted first
- ✅ Helpful count tracking

### Notification Features
- ✅ Notify question owner when answer is posted
- ✅ Notify answer author when answer is accepted
- ✅ Database notifications support

## Running Migrations

To create the database tables:

```bash
php artisan migrate
```

To rollback:

```bash
php artisan migrate:rollback
```

## Testing the API

### Example Request: Create Question
```bash
curl -X POST http://localhost/api/v1/questions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "How to implement caching in Laravel?",
    "content": "I want to improve the performance of my application...",
    "post_id": 1
  }'
```

### Example Request: Create Answer
```bash
curl -X POST http://localhost/api/v1/questions/1/answers \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "You can use Laravel's caching facade..."
  }'
```

### Example Request: Vote on Question
```bash
curl -X POST http://localhost/api/v1/questions/1/vote \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "vote_type": "upvote"
  }'
```

### Example Request: Accept Answer
```bash
curl -X POST http://localhost/api/v1/questions/1/answers/5/accept \
  -H "Authorization: Bearer {token}"
```

## Response Format

### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Question created successfully",
  "data": {
    "id": 1,
    "title": "How to implement caching?",
    "content": "...",
    "slug": "how-to-implement-caching-xxx",
    "is_resolved": false,
    "views": 0,
    "answers_count": 0,
    "vote_score": 0,
    "user_upvotes": 0,
    "user_downvotes": 0,
    "user": {...},
    "post": null,
    "accepted_answer": null,
    "answers": [],
    "current_user_vote": null,
    "created_at": "2026-02-20T...",
    "updated_at": "2026-02-20T..."
  }
}
```

### Error Response (422 Validation Error)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "content": ["The content must be at least 20 characters."]
  }
}
```

## Database Schema

### Questions Table
```sql
CREATE TABLE questions (
  id BIGINT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  post_id BIGINT NULLABLE,
  title VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  is_resolved BOOLEAN DEFAULT false,
  accepted_answer_id BIGINT NULLABLE,
  views INT DEFAULT 0,
  answers_count INT DEFAULT 0,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP NULLABLE
);
```

### Answers Table
```sql
CREATE TABLE answers (
  id BIGINT PRIMARY KEY,
  question_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  content LONGTEXT NOT NULL,
  is_accepted BOOLEAN DEFAULT false,
  helpful_count INT DEFAULT 0,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP NULLABLE
);
```

### Question Votes Table
```sql
CREATE TABLE question_votes (
  id BIGINT PRIMARY KEY,
  question_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  vote_type ENUM('upvote', 'downvote'),
  UNIQUE(question_id, user_id),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Answer Votes Table
```sql
CREATE TABLE answer_votes (
  id BIGINT PRIMARY KEY,
  answer_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  vote_type ENUM('upvote', 'downvote'),
  UNIQUE(answer_id, user_id),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

## Files Created/Modified

### New Files (24):
1. Migrations (4)
2. Models (4)
3. Form Requests (6)
4. Policies (2)
5. Resources (2)
6. Services (3)
7. Notifications (3)
8. Controllers (2)

### Modified Files (2):
1. `app/Models/User.php` - Added Q&A relationships
2. `routes/api.php` - Added Q&A routes

## Next Steps / Enhancements

### Phase 2 (Future):
- [ ] Question bounty system
- [ ] Community wiki answers (multiple authors)
- [ ] Advanced search filters (by tag, user, date range)
- [ ] Question follow notifications
- [ ] Answer comments/discussions
- [ ] Question editing history
- [ ] Reputation system based on accepted answers
- [ ] Question templates
- [ ] AI-powered answer suggestions
- [ ] Real-time notifications via WebSocket

## Support & Documentation

All endpoints are protected by JWT authentication (`auth:api` middleware).

For API documentation, see: `docs/api.rst` or `Devhub APIs.json`

## Status: ✅ COMPLETE

The Q&A feature system is fully implemented and ready for testing. Run migrations and start using the endpoints!


# 🚀 DevHub - Developer Community Platform

A modern, feature-rich social platform built for developers to share knowledge, collaborate, and grow together. Built with Laravel 12 and cutting-edge web technologies.

---

## ✨ Key Features

### 👤 User Management & Authentication
- **JWT-based Authentication** - Secure token-based authentication system
- **Social Login Integration** - Sign up/login via Google, Microsoft, and Medium
- **Email Verification** - Two-factor authentication support with OTP
- **User Profiles** - Comprehensive profiles with avatar, bio, education, skills, and social links
- **Profile Customization** - Cover images, pronouns, location, GitHub/LinkedIn integration

### 📝 Content Creation & Discovery
- **Post Management** - Create, edit, and delete posts with rich formatting
- **Comments & Discussions** - Nested comment threads for engaging discussions
- **Post Reactions** - Like, love, clap reactions on posts (powered by Laravel Reactions)
- **Reading List** - Save posts to personalized reading lists
- **Post Views** - Track and display post view counts
- **Search Functionality** - Full-text search for posts and users (powered by Algolia)
- **Search History** - Track user search history
- **Tagging System** - Organize content with tags and follow specific topics

### 👥 Social Features
- **Follow System** - Follow/unfollow users and receive updates
- **Notifications** - Real-time notifications for follows, comments, and reactions
- **User Relationships** - Track followers and following
- **User Status** - Display user availability status

### 🤖 AI-Powered Features
- **AI Post Summarization** - Automatically summarize lengthy posts using AI
- **Code Editor Integration** - Built-in code editor with AI assistance
- **Llama AI Model** - Advanced AI model integration for intelligent suggestions

### 📊 Analytics & Monitoring
- **Post Analytics** - Track post engagement metrics
- **User Visit Tracking** - Monitor user activity (powered by Laravel Visits)
- **Telescope Debugging** - Built-in Laravel Telescope for debugging and monitoring

### 🛡️ Content Moderation
- **Reporting System** - Report inappropriate content and users
- **Admin Notifications** - Notify administrators of reported content
- **Soft Deletes** - Safely archive user data while maintaining relationships

### 🔍 Advanced Search & Discovery
- **Algolia Integration** - Lightning-fast search across posts and users
- **Tag Following** - Follow tags to get curated content

### 📧 Communication
- **Email Notifications** - OTP verification, password reset, welcome emails
- **Welcome Emails** - Personalized onboarding emails
- **Verified Successfully Mails** - Email verification confirmations
- **Support Reports** - Email support for user issues

### 🌐 API Features
- **RESTful API** - Comprehensive API platform (API Platform Laravel)
- **Rate Limiting** - API throttling to prevent abuse
- **API Versioning** - V1 API endpoints for organized development
- **Sanctum Integration** - Token-based API authentication

### ☁️ Cloud Integration
- **AWS S3 Storage** - File uploads and storage on AWS S3
- **Cloudflare Tunneling** - Secure tunneling with Cloudflare

### 🎨 Frontend Integration
- **Vite Build System** - Modern JavaScript bundling with Vite
- **Vue.js Support** - Interactive frontend components

### 📦 Additional Features
- **Database Support** - SQLite for development, production-ready database
- **Job Queue** - Background job processing for async operations
- **Caching** - Multiple caching backends for performance
- **API Documentation** - Postman collections included
- **Docker Support** - Containerized application deployment
- **Testing** - PHPUnit tests with feature and unit test coverage

---

## 🛠️ Technology Stack

### Backend
- **PHP 8.3+**
- **Laravel 12**
- **JWT Authentication** (tymon/jwt-auth)
- **Algolia Search** (algolia/algoliasearch-client-php)

### Features & Libraries
- **Laravel Scout** - Full-text search
- **Laravel Sanctum** - API token authentication
- **Laravel Socialite** - Social authentication
- **Laravel Telescope** - Debugging & monitoring
- **Laravel Reactions** - Reaction system
- **Laravel Visits** - Analytics tracking
- **Laravel Tinker** - Interactive shell

### Cloud & Storage
- **AWS S3** - File storage
- **Cloudflare** - Tunneling & CDN

### Development Tools
- **Vite** - Frontend bundling
- **Postman** - API testing
- **Laravel Pint** - Code styling
- **PHPUnit** - Testing framework
- **Faker** - Database seeding

---

## 📁 Project Structure

```
app/
├── Models/              # Eloquent Models (Post, User, Comment, etc.)
├── Http/
│   ├── Controllers/     # API Controllers
│   ├── Requests/        # Form Requests
│   └── Resources/       # API Resources
├── Notifications/       # Notification Classes
├── Mail/                # Mailable Classes
├── Services/            # Business Logic Services
├── Observers/           # Eloquent Observers
├── Policies/            # Authorization Policies
└── Facades/             # Service Facades
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.3 or higher
- Composer
- Node.js & npm
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd devhub
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Generate JWT secret**
   ```bash
   php artisan jwt:secret
   ```

7. **Run migrations**
   ```bash
   php artisan migrate
   ```

8. **Seed the database (optional)**
   ```bash
   php artisan db:seed
   ```

9. **Build frontend assets**
   ```bash
   npm run build
   ```

10. **Start development server**
    ```bash
    php artisan serve
    ```

---

## 📚 API Documentation

Explore the complete API documentation in the `postman/` directory:
- **Collections** - API endpoint collections
- **Environments** - Environment configurations
- **Globals** - Global variables
- **Specs** - API specifications

---

## 🧪 Testing

Run tests with PHPUnit:

```bash
php artisan test
```

Run specific test suite:

```bash
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

---

## 🐳 Docker Support

Build and run with Docker:

```bash
docker build -t devhub .
docker run -p 8000:8000 devhub
```

Or use the provided deployment script:

```bash
./deploy.sh
```

---

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📞 Support

For support, please open an issue or contact the development team.

---

**Built with ❤️ for the developer community**

# 🚀 DevHub - Developer Community Platform

A modern, feature-rich social platform built for developers to share knowledge, collaborate, and grow together. Built with Laravel 12 and cutting-edge web technologies.

---

## ✨ Key Features

### 👤 User Management & Authentication
- **JWT-based Authentication** - Secure token-based authentication system with refresh tokens
- **Social Login Integration** - Sign up/login via Google, Microsoft, and Medium
- **Email Verification** - Email verification with OTP (One-Time Password)
- **JWT-based Authentication** - Secure token-based authentication system
- **Profile Customization** - Cover images, pronouns, location, GitHub/LinkedIn integration
- **Email Verification** - Two-factor authentication support with OTP
- **Account Deletion** - Soft and hard delete options with file cleanup

### 📝 Content Creation & Discovery
- **Post Management** - Create, edit, delete, and restore posts with rich formatting
- **Comments & Discussions** - Nested comment threads for engaging discussions
- **Comment Management** - Edit and delete comments with proper authorization
- **Post Management** - Create, edit, and delete posts with rich formatting
- **Search Functionality** - Full-text search for posts and users (powered by Algolia)
- **Search History** - Track and retrieve user search history
- **Tagging System** - Organize content with tags and follow specific topics
- **Post Reactions** - Like, love, clap reactions on posts (powered by Laravel Reactions)
- **Follower Management** - Get followers, following, and mutual connection lists
- **User Relationships** - Track followers, following, and relationship status
- **Post Views** - Track and display post view counts
- **Notifications** - Real-time notifications for:
  - New followers
  - Post comments
  - Post reactions
  - User reports
- **Search History** - Track user search history

### 🤖 AI-Powered Features
- **Code Editor Integration** - Built-in code editor with multiple language support
- **Code Execution** - Run and test code snippets in real-time
- **Follow System** - Follow/unfollow users and receive updates
- **Notifications** - Real-time notifications for follows, comments, and reactions
- **User Relationships** - Track followers and following
- **User Status** - Display user availability status
- **Activity Dashboard** - User activity statistics and insights
- **Telescope Debugging** - Built-in Laravel Telescope for debugging and monitoring

### 🛡️ Content Moderation
- **Intelligent Content Moderation** - AI-powered content moderation with:
  - Category detection (violence, sexual content, etc.)
  - Severity levels (low, medium, high, critical)
- **Code Editor Integration** - Built-in code editor with AI assistance
- **Reporting System** - Report inappropriate content and users with reasons
- **Admin Notifications** - Notify administrators of reported content and users
- **Permanent Deletion** - Secure permanent deletion with file cleanup

- **Post Analytics** - Track post engagement metrics
- **User Visit Tracking** - Monitor user activity (powered by Laravel Visits)
  - OTP verification emails
  - Password reset emails
  - Welcome emails for new users
  - Password updated confirmation
  - Account verification emails
  - Support report emails
- **Email Templating** - Professional email templates
- **Notification Management** - User preference for notification types

### 🌐 API Features
- **Reporting System** - Report inappropriate content and users
- **Admin Notifications** - Notify administrators of reported content
- **Soft Deletes** - Safely archive user data while maintaining relationships
  - Post inline images
- **Cloudflare Tunneling** - Secure tunneling and DDoS protection
- **CDN Integration** - Fast content delivery globally

### 🎨 Frontend Integration
- **Vite Build System** - Modern JavaScript bundling with Vite
- **Vue.js Support** - Interactive frontend components
- **Responsive Design** - Mobile-first responsive design

### 📦 Additional Features
- **Database Support** - SQLite for development, production-ready database
- **Job Queue** - Background job processing for:
  - Email sending
  - Image processing
  - Notification delivery
- **Caching** - Multiple caching backends for:
  - Complete endpoint documentation
  - Environment configurations
  - Global variables and helpers
  - API specifications
- **Email Notifications** - OTP verification, password reset, welcome emails
- **Welcome Emails** - Personalized onboarding emails
- **Verified Successfully Mails** - Email verification confirmations
- **Support Reports** - Email support for user issues
- **JWT Authentication** (tymon/jwt-auth)
- **Algolia Search** (algolia/algoliasearch-client-php)

### Features & Libraries
- **Laravel Scout** - Full-text search
- **Laravel Sanctum** - API token authentication
- **Rate Limiting** - API throttling to prevent abuse
- **Laravel Telescope** - Debugging & monitoring
- **Laravel Reactions** - Reaction system
- **Sanctum Integration** - Token-based API authentication
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
- **AWS S3 Storage** - File uploads and storage on AWS S3
- **Cloudflare Tunneling** - Secure tunneling with Cloudflare
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
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

- **Job Queue** - Background job processing for async operations
- **Caching** - Multiple caching backends for performance
- **API Documentation** - Postman collections included
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
- **Testing** - PHPUnit tests with feature and unit test coverage
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

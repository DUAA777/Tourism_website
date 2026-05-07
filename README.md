# Tourism Website

Tourism Website is a Laravel-based tourism recommendation platform. It helps users explore hotels, restaurants, activities, destinations, and places while also providing an AI-assisted chatbot recommendation system. The system includes public browsing pages, authentication, profile features, reviews, admin management, and chatbot sessions.

## Project Type

Final Year Project — Tourism Recommendation System

## Main Features

- Public home, about, contact, destinations, and places pages
- Hotel listing and hotel details pages
- Restaurant listing and restaurant details pages
- Destination search
- Static places list and place details
- User registration and login
- Google authentication support
- Password reset
- User profile management
- Profile photo deletion
- User reviews
- Chatbot page for tourism recommendations
- Chat sessions and chat message history
- Structured recommendation payloads for hotels, restaurants, activities, and trip plans
- Python chatbot service integration
- Fallback chatbot response if the Python service is unavailable
- Admin dashboard
- Admin management for:
  - Users
  - Restaurants
  - Hotels
- Bulk restaurant and hotel operations
- Hotel soft delete, restore, and force delete support
- Export support through Laravel Excel

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Blade, Tailwind CSS, JavaScript
- **Build Tool:** Vite
- **Authentication:** Laravel authentication + Google OAuth through Socialite
- **Database:** MySQL / MariaDB / SQLite depending on `.env`
- **Recommendation Layer:** Laravel recommendation service
- **AI Layer:** External Python chatbot service
- **Exports:** Maatwebsite Excel
- **Testing:** PHPUnit

## Requirements

Make sure you have:

- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL, MariaDB, SQLite, or another Laravel-supported database
- Git
- Python, if you want to run the external chatbot service locally

## Installation

Clone the repository:

```bash
git clone https://github.com/DUAA777/Tourism_website.git
cd Tourism_website
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database inside `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tourism_website
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:

```bash
php artisan migrate
```

If seeders are available, run:

```bash
php artisan db:seed
```

Link storage:

```bash
php artisan storage:link
```

Start the frontend build process:

```bash
npm run dev
```

Start Laravel:

```bash
php artisan serve
```

The app should now be available at:

```text
http://127.0.0.1:8000
```

## Chatbot Service Setup

The Laravel chatbot controller sends requests to an external chatbot API.

The default local chatbot URL is:

```text
http://127.0.0.1:5000/chat
```

If your chatbot service uses another URL, configure it in your Laravel service settings or `.env`, depending on your local setup.

Example:

```env
CHATBOT_BASE_URL=http://127.0.0.1:5000
```

Start the Python chatbot service separately before using the chatbot page. If the service is offline, the Laravel app can still return a fallback response.

## Google Authentication Setup

To enable Google login, add your Google OAuth credentials to `.env`:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

Also add the same redirect URI inside your Google Cloud Console.

## Important Routes

Public routes include:

- `/`
- `/aboutUs`
- `/contactUs`
- `/destinations`
- `/hotels`
- `/hotels/{id}`
- `/restaurants`
- `/restaurants/{id}`
- `/places`
- `/places/{slug}`
- `/search-destinations`

Authentication routes include:

- `/login`
- `/register`
- `/logout`
- `/forgot-password`
- `/reset-password`
- `/auth/google`
- `/auth/google/callback`

Authenticated user routes include:

- `/chatbot`
- `/chatbot/message`
- `/chatbot/new-session`
- `/reviews`
- `/profile`

Admin routes are grouped under:

```text
/admin
```

## Project Structure

```text
app/
  Console/Commands/       Custom commands
  Http/Controllers/       Public, auth, chatbot, profile, and admin controllers
  Imports/                Excel import/export related logic
  Mail/                   Mail classes
  Models/                 Eloquent models
  Providers/              Laravel service providers
  Services/               Recommendation service

database/
  migrations/             Database table definitions
  seeders/                Optional seed data

resources/
  views/                  Blade templates
  css/                    CSS assets
  js/                     JavaScript assets

routes/
  web.php                 Main routes

public/                   Public assets
storage/                  Uploaded and generated files
```

## Main Models

- User
- Hotel
- Restaurant
- Activity
- Review
- ChatSession
- ChatMessage

## Recommendation System Overview

The chatbot does not only return random text. The Laravel side prepares structured data first. It uses the recommendation service to build response data from the available hotels, restaurants, activities, and trip-planning context. Then the chatbot service can turn that structured recommendation data into a natural response.

The chatbot flow is:

1. User sends a message.
2. Laravel validates the message and chat session.
3. The system stores the user message.
4. The recommendation layer prepares structured results.
5. Laravel sends the message, history, and structured recommendation context to the Python chatbot API.
6. The chatbot API returns a natural language answer.
7. Laravel stores the assistant response.
8. The frontend receives the reply, entity links, session id, and structured data.

## Admin Notes

The admin section allows management of users, hotels, and restaurants. Hotel management also supports soft delete, restore, and force delete behavior.

## Testing

Run the test suite with:

```bash
php artisan test
```

or:

```bash
composer test
```

## Future Improvements

- Add clearer documentation for the Python chatbot service
- Add database seeders for demo hotels, restaurants, and activities
- Improve recommendation scoring explanations
- Add more advanced filtering for restaurants and hotels
- Add image optimization for uploaded files
- Add analytics for most searched destinations
- Add more automated tests
- Improve admin dashboard charts and reporting

## Author

Tourism Website was developed as a Final Year Project tourism recommendation system.

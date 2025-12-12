module.exports = {
    apps: [
        {
            name: "otp-login-laravel",
            script: "artisan",
            interpreter: "php",
            args: "serve --host=0.0.0.0 --port=8001",
            instances: 1,
            autorestart: true,
            watch: false,
            max_memory_restart: "1G",
            env: {
                APP_ENV: "production",
            },
            env_development: {
                APP_ENV: "development",
            },
        },
    ],
};

import os
import sqlite3
from pathlib import Path

try:
    import pymysql
except ImportError:
    pymysql = None


CURRENT_DIR = Path(__file__).resolve().parent


def resolve_project_root() -> Path:
    candidates = [
        CURRENT_DIR,
        CURRENT_DIR.parent,
        CURRENT_DIR.parent.parent,
    ]

    for candidate in candidates:
        if (candidate / "artisan").exists() or (candidate / ".env").exists():
            return candidate

    return CURRENT_DIR


PROJECT_ROOT = resolve_project_root()
ENV_PATH = PROJECT_ROOT / ".env"


def load_env_file(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}

    if not path.exists():
        return values

    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        values[key.strip()] = value.strip().strip('"').strip("'")

    return values


ENV = load_env_file(ENV_PATH)


def env_value(key: str, default: str) -> str:
    value = os.getenv(key)
    if value is None:
        value = ENV.get(key, default)

    if value is None:
        return default

    value = str(value).strip()
    return value if value != "" else default


def env_int(key: str, default: int) -> int:
    raw = env_value(key, str(default))
    try:
        return int(raw)
    except (TypeError, ValueError):
        return default


DB_CONNECTION = env_value("DB_CONNECTION", "sqlite").lower()
DB_DATABASE = env_value("DB_DATABASE", "database/database.sqlite")

if DB_CONNECTION == "sqlite":
    database_path = Path(DB_DATABASE)
    if not database_path.is_absolute():
        database_path = (PROJECT_ROOT / database_path).resolve()

    DB_CONFIG = {
        "connection": "sqlite",
        "database": str(database_path),
    }
else:
    DB_CONFIG = {
        "connection": "mysql",
        "host": env_value("DB_HOST", "127.0.0.1"),
        "port": env_int("DB_PORT", 3306),
        "user": env_value("DB_USERNAME", "root"),
        "password": env_value("DB_PASSWORD", ""),
        "database": env_value("DB_DATABASE", ""),
        "charset": "utf8mb4",
        "cursorclass": pymysql.cursors.DictCursor if pymysql else None,
    }


def get_connection():
    if DB_CONFIG["connection"] == "sqlite":
        return sqlite3.connect(DB_CONFIG["database"])

    if pymysql is None:
        raise RuntimeError(
            "PyMySQL is required for MySQL mode, but it is not installed. "
            "Either install pymysql or switch the Laravel app to SQLite."
        )

    connection_config = {k: v for k, v in DB_CONFIG.items() if k != "connection" and v is not None}
    return pymysql.connect(**connection_config)

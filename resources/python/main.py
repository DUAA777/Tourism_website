import os
import sys
from pathlib import Path

from flask import Flask

PYTHON_DIR = Path(__file__).resolve().parent
if str(PYTHON_DIR) not in sys.path:
    sys.path.insert(0, str(PYTHON_DIR))

try:
    from flask_cors import CORS
except ImportError:
    CORS = None

from routes import register_routes

app = Flask(__name__)

if CORS is not None:
    CORS(app)

# Register all routes
register_routes(app)

if __name__ == "__main__":
    app.run(
        host=os.getenv("SIMILARITY_SERVICE_HOST", "127.0.0.1"),
        port=int(os.getenv("SIMILARITY_SERVICE_PORT", "5001")),
        debug=os.getenv("SIMILARITY_SERVICE_DEBUG", "false").lower() == "true",
    )

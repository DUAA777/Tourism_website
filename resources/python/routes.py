import sys
from pathlib import Path

from flask import request, jsonify


PYTHON_DIR = Path(__file__).resolve().parent
for path in (PYTHON_DIR,):
    path_str = str(path)
    if path_str not in sys.path:
        sys.path.insert(0, path_str)

from services.restaurants import RestaurantService
from services.hotels import HotelService

# Initialize services
restaurant_service = RestaurantService()
hotel_service = HotelService()


def register_routes(app):
    # Restaurant Routes
    @app.route("/recommend", methods=["POST"])
    def recommend():
        result, status_code = restaurant_service.recommend(request.json or {})
        return jsonify(result), status_code

    @app.route("/smart-recommend", methods=["POST"])
    def smart_recommend():
        params = request.json or {}
        if 'recommendation_type' not in params:
            params['recommendation_type'] = 'smart'
        result, status_code = restaurant_service.recommend(params)
        return jsonify(result), status_code

    @app.route("/similar/<int:restaurant_id>", methods=["GET"])
    def get_similar_restaurants(restaurant_id):
        limit = int(request.args.get('limit', 6))
        result, status_code = restaurant_service.get_similar_restaurants(restaurant_id, limit)
        return jsonify(result), status_code

    @app.route("/restaurant-stats", methods=["GET"])
    def get_restaurant_stats():
        result, status_code = restaurant_service.get_stats()
        return jsonify(result), status_code

    @app.route("/reload-restaurants", methods=["GET"])
    def reload_restaurants():
        result, status_code = restaurant_service.reload()
        return jsonify(result), status_code

    @app.route("/similar-hotels/<int:hotel_id>", methods=["GET"])
    def get_similar_hotels(hotel_id):
        limit = int(request.args.get('limit', 6))
        result, status_code = hotel_service.get_similar_hotels(hotel_id, limit)
        return jsonify(result), status_code

    @app.route("/hotel-stats", methods=["GET"])
    def get_hotel_stats():
        result, status_code = hotel_service.get_stats()
        return jsonify(result), status_code

    @app.route("/reload-hotels", methods=["GET"])
    def reload_hotels():
        result, status_code = hotel_service.reload()
        return jsonify(result), status_code

    # Legacy/Combined Routes (for backward compatibility)
    @app.route("/reload", methods=["GET"])
    def reload_data():
        """Reload both restaurants and hotels"""
        rest_result, _ = restaurant_service.reload()
        hotel_result, _ = hotel_service.reload()
        return jsonify({
            "restaurants": rest_result,
            "hotels": hotel_result
        })

    @app.route("/stats", methods=["GET"])
    def get_stats():
        """Get combined statistics"""
        rest_stats, _ = restaurant_service.get_stats()
        hotel_stats, _ = hotel_service.get_stats()

        if "error" in rest_stats:
            return jsonify({"error": "No restaurant data"}), 500

        combined_stats = {
            "restaurants": rest_stats,
            "hotels": hotel_stats
        }
        return jsonify(combined_stats)

    @app.route("/test", methods=["GET"])
    def test():
        """Test endpoint to verify services are loaded"""
        rest_count = len(restaurant_service.restaurants_df) if restaurant_service.restaurants_df is not None else 0
        hotel_count = len(hotel_service.hotels_df) if hotel_service.hotels_df is not None else 0

        return jsonify({
            "status": "ok",
            "restaurants_loaded": rest_count,
            "hotels_loaded": hotel_count
        })

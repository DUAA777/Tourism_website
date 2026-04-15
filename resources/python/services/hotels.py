import pandas as pd
import numpy as np
import re
from collections import Counter
from datetime import datetime
import warnings

try:
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity
    from sklearn.cluster import KMeans
    SKLEARN_AVAILABLE = True
except ImportError:
    TfidfVectorizer = None
    cosine_similarity = None
    KMeans = None
    SKLEARN_AVAILABLE = False

warnings.filterwarnings('ignore')
from config import get_connection


class HotelService:
    def __init__(self):
        self.hotels_df = None
        self.tfidf_vectorizer = None
        self.tfidf_matrix = None
        self.kmeans_model = None
        self.location_mapping = self.create_location_mapping()
        self.load_hotels()

    def build_hotel_dedup_key(self, row):
        """Build a stable display-level key for hotel deduplication."""
        hotel_name = self.clean_text(row.get("hotel_name", ""))
        address = self.clean_text(row.get("address", ""))

        if hotel_name and address:
            return f"{hotel_name}|{address}"
        if hotel_name:
            return hotel_name

        hotel_id = row.get("id")
        return f"id:{hotel_id}" if hotel_id is not None else ""

    def build_similarity_family_key(self, row):
        """Collapse manually-reviewed multi-unit properties into one recommendation."""
        hotel_name = self.clean_text(row.get("hotel_name", ""))

        curated_families = (
            (r"\bsands\b", "family:sands-guesthouse"),
            (r"\bbeit chams\b", "family:beit-chams"),
            (r"\barcades\b.*\bbahsa\b", "family:arcades-de-bahsa"),
        )

        for pattern, family_key in curated_families:
            if re.search(pattern, hotel_name):
                return family_key

        return self.build_hotel_dedup_key(row)

    def deduplicate_hotels_df(self, df):
        """Remove duplicate hotel rows while keeping the richest record."""
        if df is None or df.empty:
            return df

        dedupe_df = df.copy()
        dedupe_df["_hotel_dedup_key"] = dedupe_df.apply(self.build_hotel_dedup_key, axis=1)

        text_fields = [
            "hotel_name",
            "address",
            "room_type",
            "bed_info",
            "nearby_landmark",
            "description",
            "stay_details",
            "hotel_image",
            "price_per_night",
        ]
        available_text_fields = [field for field in text_fields if field in dedupe_df.columns]

        if available_text_fields:
            dedupe_df["_data_completeness"] = dedupe_df[available_text_fields].apply(
                lambda row: sum(1 for value in row if value and not pd.isna(value)),
                axis=1
            )
        else:
            dedupe_df["_data_completeness"] = 0

        dedupe_df = dedupe_df.sort_values(
            by=["_hotel_dedup_key", "_data_completeness", "id"],
            ascending=[True, False, True]
        )

        before_count = len(dedupe_df)
        dedupe_df = dedupe_df.drop_duplicates(subset=["_hotel_dedup_key"], keep="first")
        removed_count = before_count - len(dedupe_df)

        if removed_count > 0:
            print(f"Removed {removed_count} duplicate hotel rows before modeling")

        return dedupe_df.drop(columns=["_hotel_dedup_key", "_data_completeness"], errors="ignore")

    def deduplicate_similar_hotels(self, df):
        """Keep only one visible card per hotel in similar-results output."""
        if df is None or df.empty:
            return df

        dedupe_df = df.copy()
        dedupe_df["_hotel_dedup_key"] = dedupe_df.apply(self.build_similarity_family_key, axis=1)
        dedupe_df = dedupe_df.sort_values(by=["final_similarity", "id"], ascending=[False, True])
        dedupe_df = dedupe_df.drop_duplicates(subset=["_hotel_dedup_key"], keep="first")

        return dedupe_df.drop(columns=["_hotel_dedup_key"], errors="ignore")

    def create_location_mapping(self):
        """Create mapping from neighborhoods to cities/districts in Lebanon"""
        location_map = {
            # Beirut neighborhoods
            'hamra': 'Beirut',
            'hamra street': 'Beirut',
            'verdun': 'Beirut',
            'ain el mraisseh': 'Beirut',
            'ras beirut': 'Beirut',
            'manara': 'Beirut',
            'koraytem': 'Beirut',
            'mazraa': 'Beirut',
            'tarik el jdideh': 'Beirut',
            'badaro': 'Beirut',
            'ashrafieh': 'Beirut',
            'achrafieh': 'Beirut',
            'gemmayze': 'Beirut',
            'gemmayzeh': 'Beirut',
            'mar mikhael': 'Beirut',
            'mar mkhayel': 'Beirut',
            'sodeco': 'Beirut',
            'sioufi': 'Beirut',
            'jbail': 'Byblos',
            'byblos': 'Byblos',
            'jbeil': 'Byblos',
            'jounieh': 'Jounieh',
            'kaslik': 'Jounieh',
            'ghazir': 'Jounieh',
            'zouk': 'Jounieh',
            'zouk mosbeh': 'Jounieh',
            'zouk mikael': 'Jounieh',
            'batroun': 'Batroun',
            'chekka': 'Batroun',
            'koura': 'Koura',
            'kfaraaka': 'Koura',
            'amchit': 'Amchit',
            'bcharre': 'Bcharre',
            'bcharreh': 'Bcharre',
            'ehden': 'Ehden',
            'zgharta': 'Zgharta',
            'tripoli': 'Tripoli',
            'trablos': 'Tripoli',
            'mina': 'Tripoli',
            'sidon': 'Sidon',
            'saida': 'Sidon',
            'tyre': 'Tyre',
            'sour': 'Tyre',
            'zahlé': 'Zahle',
            'zahle': 'Zahle',
            'baalbek': 'Baalbek',
            'bikfaya': 'Bikfaya',
            'broummana': 'Broummana',
            'beit mery': 'Beit Mery',
            'baabda': 'Baabda',
            'hadath': 'Hadath',
            'hazmieh': 'Hazmieh',
            'sin el fil': 'Sin El Fil',
            'dora': 'Dora',
            'jal el dib': 'Jal El Dib',
            'antelias': 'Antelias',
            'dbayeh': 'Dbayeh',
            'dbaye': 'Dbayeh',
            'naccache': 'Naccache',
            'beirut international airport': 'Beirut',
            'beirut airport': 'Beirut',
            'beirut digital district': 'Beirut',
            'beirut souks': 'Beirut',
            'zaitunay bay': 'Beirut',
            'waterfront': 'Beirut',
            'beirut waterfront': 'Beirut'
        }
        return location_map

    def normalize_location(self, location_text):
        """Normalize location to standard city/district name"""
        if not location_text or pd.isna(location_text):
            return "Unknown"

        location_lower = str(location_text).lower().strip()

        # Check direct mapping
        for neighborhood, city in self.location_mapping.items():
            if neighborhood in location_lower:
                return city

        # Check if location already is a city name
        cities = ['beirut', 'byblos', 'jbeil', 'jounieh', 'batroun', 'tripoli',
                  'sidon', 'tyre', 'zahle', 'baalbek', 'keserwan', 'koura',
                  'zgharta', 'ehden', 'bcharre', 'amchit', 'bikfaya']

        for city in cities:
            if city in location_lower:
                return city.title()

        return location_text.title()

    def clean_text(self, text):
        """Enhanced text cleaning"""
        if not text or pd.isna(text):
            return ""
        text = str(text).lower().strip()
        # Keep alphanumeric, spaces, commas, and hyphens
        text = re.sub(r"[^\w\s,-]", "", text)
        # Remove extra whitespace
        text = re.sub(r'\s+', ' ', text)
        return text

    def parse_price(self, price_str):
        """Extract numeric price from price string with better handling"""
        if not price_str or pd.isna(price_str):
            return None
        try:
            # Handle various price formats: "$100", "100 USD", "100.00", etc.
            price_num = re.findall(r'[\d,]+\.?\d*', str(price_str))
            if price_num:
                # Remove commas and convert to float
                price_clean = price_num[0].replace(',', '')
                return float(price_clean)
            return None
        except:
            return None

    def extract_keywords(self, text, top_n=5):
        """Extract top keywords from text with better filtering"""
        if not text or pd.isna(text):
            return []

        words = str(text).lower().split()

        # Enhanced stopwords list for hotels
        stopwords = {
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'be',
            'been', 'has', 'have', 'had', 'hotel', 'room', 'stay', 'night', 'per',
            'free', 'wifi', 'parking', 'breakfast', 'included', 'service', 'offers'
        }

        words = [w for w in words if w not in stopwords and len(w) > 2]

        # Get word frequencies
        word_freq = Counter(words)

        # Extract keywords
        keywords = []
        for word, count in word_freq.most_common(top_n):
            keywords.append(word)

        return keywords

    def simple_text_similarity(self, text1, text2):
        """Fallback similarity when scikit-learn is unavailable."""
        tokens1 = set(self.clean_text(text1).split())
        tokens2 = set(self.clean_text(text2).split())

        if not tokens1 or not tokens2:
            return 0.0

        intersection = len(tokens1 & tokens2)
        union = len(tokens1 | tokens2)

        return intersection / union if union > 0 else 0.0

    def parse_distance(self, distance_str):
        """Extract numeric distance value from string"""
        if not distance_str or pd.isna(distance_str):
            return None
        try:
            distance_num = re.findall(r'[\d,]+\.?\d*', str(distance_str))
            if distance_num:
                return float(distance_num[0].replace(',', ''))
            return None
        except:
            return None

    def calculate_popularity_score(self, df):
        """Calculate enhanced popularity score for hotels"""
        # Rating score weight
        rating_weight = df['rating_score'] / 5.0

        # Review count weight (normalized)
        if 'review_count' in df.columns and df['review_count'].max() > 0:
            review_weight = df['review_count'] / df['review_count'].max()
        else:
            review_weight = 0.5

        # Location popularity based on normalized location
        if 'normalized_location' in df.columns:
            location_boost = df['normalized_location'].map(
                df['normalized_location'].value_counts(normalize=True)
            ).fillna(0.5)
        else:
            location_boost = 0.5

        # Combined popularity score
        popularity = (rating_weight * 0.6 + review_weight * 0.3 + location_boost * 0.1)

        return popularity.clip(0, 5)

    def create_hotel_soup(self, row):
        """Create weighted feature soup for similarity calculation"""
        components = []

        # Primary features (highest weight)
        room_type = self.clean_text(row.get("room_type", ""))
        if room_type:
            components.extend([room_type] * 5)

        bed_info = self.clean_text(row.get("bed_info", ""))
        if bed_info:
            components.extend([bed_info] * 4)

        # Secondary features
        nearby_landmark = self.clean_text(row.get("nearby_landmark", ""))
        if nearby_landmark:
            components.extend([nearby_landmark] * 3)

        # Location features
        location = self.clean_text(row.get("normalized_location", row.get("address", "")))
        if location:
            components.extend([location] * 2)

        # Distance features
        distance_features = []
        if row.get("distance_from_center"):
            distance_features.append(self.clean_text(row["distance_from_center"]))
        if row.get("distance_from_beach"):
            distance_features.append(self.clean_text(row["distance_from_beach"]))
        if distance_features:
            components.append(" ".join(distance_features) * 2)

        # Description and stay details
        description = self.clean_text(row.get("description", ""))
        if description:
            components.append(description)

        stay_details = self.clean_text(row.get("stay_details", ""))
        if stay_details:
            components.append(stay_details)

        return " ".join(components)

    def load_hotels(self):
        """Load hotels from MySQL with enhanced features"""
        try:
            print("Loading hotels from database...")

            conn = get_connection()

            cursor = conn.cursor()
            try:
                cursor.execute("SELECT * FROM hotels")
                data = cursor.fetchall()
                columns = [desc[0] for desc in cursor.description]
            finally:
                cursor.close()

            conn.close()

            if not data:
                print("No hotels found")
                self.hotels_df = pd.DataFrame()
                return

            # Convert to DataFrame
            df = pd.DataFrame(data, columns=columns)
            print(f"Raw hotel data loaded: {len(df)} rows")

            df = self.deduplicate_hotels_df(df)
            print(f"Hotel rows after deduplication: {len(df)}")

            # Preserve display fields; text is cleaned later where feature engineering needs it.
            text_columns = ['hotel_name', 'room_type', 'bed_info', 'nearby_landmark',
                            'address', 'description', 'stay_details']
            for col in text_columns:
                if col in df.columns:
                    df[col] = df[col].fillna("")

            # Add normalized location (city/district level)
            df['normalized_location'] = df['address'].apply(self.normalize_location)
            df['normalized_location_clean'] = df['normalized_location'].apply(self.clean_text)

            # Parse rating score
            df["rating_score"] = pd.to_numeric(df["rating_score"], errors="coerce").fillna(3.0)
            df["rating_score"] = df["rating_score"].clip(0, 5)

            # Parse price and create numeric price column
            df["price_numeric"] = df["price_per_night"].apply(self.parse_price)

            # Parse distances
            df["distance_center_numeric"] = df["distance_from_center"].apply(self.parse_distance)
            df["distance_beach_numeric"] = df["distance_from_beach"].apply(self.parse_distance)

            # Create price tier based on numeric price
            def create_price_tier(price):
                if pd.isna(price) or price is None:
                    return "unknown"
                if price < 100:
                    return "budget"
                elif price < 200:
                    return "mid-range"
                elif price < 400:
                    return "premium"
                else:
                    return "luxury"

            df["price_tier"] = df["price_numeric"].apply(create_price_tier)

            # Review count
            df["review_count"] = pd.to_numeric(df["review_count"], errors="coerce").fillna(0)

            # Calculate popularity score
            df["popularity"] = self.calculate_popularity_score(df)

            # Create hotel features for similarity
            df["hotel_features"] = df.apply(self.create_hotel_soup, axis=1)

            # Extract keywords
            df["keywords"] = df.apply(
                lambda row: self.extract_keywords(
                    f"{row.get('room_type', '')} {row.get('bed_info', '')} {row.get('nearby_landmark', '')} {row.get('description', '')}",
                    top_n=8
                ),
                axis=1
            )

            # Fill NaN values with empty strings
            df = df.fillna("")

            # Create feature clusters for diverse recommendations
            if SKLEARN_AVAILABLE and len(df) > 10:
                try:
                    self.tfidf_vectorizer = TfidfVectorizer(
                        max_features=200,
                        stop_words='english',
                        ngram_range=(1, 2)
                    )
                    X = self.tfidf_vectorizer.fit_transform(df["hotel_features"])

                    # Determine optimal number of clusters
                    n_clusters = min(max(3, len(df) // 8), 15)
                    self.kmeans_model = KMeans(n_clusters=n_clusters, random_state=42, n_init=10)
                    df["cluster"] = self.kmeans_model.fit_predict(X)

                    # Store TF-IDF matrix for later use
                    self.tfidf_matrix = X

                except Exception as e:
                    print(f"Clustering error: {e}")
                    df["cluster"] = 0
                    self.tfidf_matrix = None
            else:
                df["cluster"] = 0
                self.tfidf_matrix = None

            self.hotels_df = df
            print(f"Loaded hotels: {len(self.hotels_df)}")
            print(f"Unique normalized locations: {df['normalized_location'].nunique()}")
            print(f"Location distribution: {df['normalized_location'].value_counts().head(10).to_dict()}")
            print(f"Price tiers: {df['price_tier'].value_counts().to_dict()}")
            print(f"Clusters created: {df['cluster'].nunique() if 'cluster' in df.columns else 0}")

        except Exception as e:
            print(f"Hotel database load error: {e}")
            import traceback
            traceback.print_exc()
            self.hotels_df = pd.DataFrame()
            self.tfidf_matrix = None

    def get_diverse_hotel_recommendations(self, df, n=40):
        """Get diverse hotel recommendations by sampling from different clusters"""
        if 'cluster' not in df.columns or df['cluster'].nunique() <= 1:
            return df.sort_values(by='final_score', ascending=False).head(n)

        result = pd.DataFrame()
        clusters = df['cluster'].unique()
        per_cluster = max(1, n // len(clusters))

        for cluster in clusters:
            cluster_df = df[df['cluster'] == cluster].sort_values(by='final_score', ascending=False)
            take_n = min(per_cluster, len(cluster_df))
            result = pd.concat([result, cluster_df.head(take_n)])

        if len(result) < n:
            remaining = df[~df.index.isin(result.index)].sort_values(by='final_score', ascending=False)
            result = pd.concat([result, remaining.head(n - len(result))])

        return result.head(n)

    def prepare_hotel_output(self, df):
        """Prepare hotel DataFrame for JSON output"""
        output_dict = df.to_dict(orient="records")

        for item in output_dict:
            for key, value in list(item.items()):
                if hasattr(value, 'isoformat'):
                    item[key] = value.isoformat() if value else None
                elif pd.isna(value):
                    item[key] = None
                elif isinstance(value, (np.integer, np.floating)):
                    item[key] = float(value) if isinstance(value, np.floating) else int(value)
                elif isinstance(value, np.ndarray):
                    item[key] = value.tolist() if len(value) > 0 else []

        return output_dict

    def calculate_user_preference_vector(self, user_persona):
        """Calculate user preference vector using TF-IDF"""
        if not SKLEARN_AVAILABLE:
            return user_persona

        if self.tfidf_vectorizer is None:
            # Initialize vectorizer if not exists
            self.tfidf_vectorizer = TfidfVectorizer(
                stop_words="english",
                ngram_range=(1, 3),
                min_df=1,
                max_features=9000,
                sublinear_tf=True
            )
            # Fit on all hotel features
            self.tfidf_vectorizer.fit(self.hotels_df["hotel_features"])

        return self.tfidf_vectorizer.transform([user_persona])


    def get_similar_hotels(self, hotel_id, limit=6):
        """Get similar hotels based on a specific hotel ID with enhanced features"""
        if self.hotels_df is None or len(self.hotels_df) == 0:
            return {"error": "No hotels data available"}, 500

        df = self.hotels_df.copy()

        target_hotel = df[df['id'] == hotel_id]

        if len(target_hotel) == 0:
            return {"error": f"Hotel with ID {hotel_id} not found"}, 404

        # Extract target hotel features
        target_idx = target_hotel.index[0]
        target_room_type = target_hotel['room_type'].values[0] if 'room_type' in target_hotel.columns else ''
        target_price_tier = target_hotel['price_tier'].values[0] if 'price_tier' in target_hotel.columns else ''
        target_location = target_hotel['normalized_location'].values[
            0] if 'normalized_location' in target_hotel.columns else ''
        target_bed_info = target_hotel['bed_info'].values[0] if 'bed_info' in target_hotel.columns else ''
        target_cluster = target_hotel['cluster'].values[0] if 'cluster' in target_hotel.columns else None

        # Calculate similarities using cached matrix if available
        if SKLEARN_AVAILABLE:
            if self.tfidf_matrix is not None and self.tfidf_matrix.shape[0] == len(df):
                target_vector = self.tfidf_matrix[target_idx]
                similarities = cosine_similarity(target_vector, self.tfidf_matrix).flatten()
            else:
                # Recalculate if necessary
                tfidf = TfidfVectorizer(
                    stop_words="english",
                    ngram_range=(1, 3),
                    min_df=1,
                    max_features=9000,
                    sublinear_tf=True
                )
                tfidf_matrix = tfidf.fit_transform(df["hotel_features"])
                target_vector = tfidf_matrix[target_idx]
                similarities = cosine_similarity(target_vector, tfidf_matrix).flatten()
        else:
            target_text = target_hotel["hotel_features"].values[0]
            similarities = df["hotel_features"].apply(
                lambda text: self.simple_text_similarity(target_text, text)
            ).to_numpy()

        df['similarity_score'] = similarities
        similar_df = df[df['id'] != hotel_id].copy()

        # Enhanced similarity scoring with multiple factors
        similar_df['room_type_match'] = similar_df['room_type'].apply(
            lambda x: 1.0 if x == target_room_type
            else 0.6 if target_room_type in str(x) or str(x) in target_room_type
            else 0.0
        )

        similar_df['bed_info_match'] = similar_df['bed_info'].apply(
            lambda x: 1.0 if x == target_bed_info
            else 0.4 if target_bed_info in str(x) or str(x) in target_bed_info
            else 0.0
        )

        similar_df['price_match'] = similar_df['price_tier'].apply(
            lambda x: 1.0 if x == target_price_tier
            else 0.5 if x != 'unknown'
            else 0.0
        )

        similar_df['location_match'] = similar_df['normalized_location'].apply(
            lambda x: 1.0 if x == target_location
            else 0.3 if target_location in str(x) or str(x) in target_location
            else 0.0
        )

        # Calculate final similarity score with weights
        similar_df['final_similarity'] = (
                similar_df['similarity_score'] * 0.50 +
                similar_df['room_type_match'] * 0.25 +
                similar_df['bed_info_match'] * 0.10 +
                similar_df['location_match'] * 0.10 +
                similar_df['price_match'] * 0.05
        )

        # Boost same-cluster hotels
        if target_cluster is not None and 'cluster' in similar_df.columns:
            similar_df['cluster_boost'] = similar_df['cluster'].apply(
                lambda x: 0.1 if x == target_cluster else 0
            )
            similar_df['final_similarity'] += similar_df['cluster_boost']

        # Get top similar hotels
        similar_hotels = similar_df.nlargest(max(limit * 3, limit), 'final_similarity')
        similar_hotels = self.deduplicate_similar_hotels(similar_hotels).head(limit)

        # Remove internal columns
        result_df = similar_hotels.drop(
            columns=['hotel_features', 'keywords', 'similarity_score',
                     'room_type_match', 'bed_info_match', 'price_match', 'location_match',
                     'final_similarity', 'rating_norm', 'popularity_norm',
                     'price_numeric', 'cluster_boost', 'distance_center_numeric',
                     'distance_beach_numeric', 'normalized_location_clean'],
            errors='ignore'
        )

        if 'cluster' in result_df.columns:
            result_df = result_df.drop(columns=['cluster'], errors='ignore')

        output_dict = self.prepare_hotel_output(result_df)

        return {
            'hotel_id': hotel_id,
            'hotel_name': target_hotel['hotel_name'].values[0],
            'hotel_location': target_location,
            'count': len(output_dict),
            'similar_hotels': output_dict
        }, 200

    def get_stats(self):
        """Get comprehensive hotel statistics"""
        if self.hotels_df is None or len(self.hotels_df) == 0:
            return {"error": "No data"}, 500

        stats = {
            "total_hotels": len(self.hotels_df),
            "avg_rating": float(self.hotels_df["rating_score"].mean()),
            "avg_price": float(self.hotels_df["price_numeric"].mean()) if self.hotels_df["price_numeric"].mean() else 0,
            "rating_distribution": self.hotels_df["rating_score"].value_counts().sort_index().to_dict(),
            "top_locations_by_city": self.hotels_df["normalized_location"].value_counts().head(10).to_dict(),
            "top_neighborhoods": self.hotels_df["address"].value_counts().head(10).to_dict(),
            "top_room_types": self.hotels_df["room_type"].value_counts().head(10).to_dict(),
            "price_tiers": self.hotels_df["price_tier"].value_counts().to_dict(),
            "avg_review_count": int(self.hotels_df["review_count"].mean()),
            "unique_cities": self.hotels_df["normalized_location"].nunique(),
            "unique_room_types": self.hotels_df["room_type"].nunique()
        }

        return stats, 200

    def reload(self):
        """Reload hotels data and refresh models"""
        self.location_mapping = self.create_location_mapping()
        self.load_hotels()
        return {
            "message": "Hotels reloaded successfully",
            "count": len(self.hotels_df) if self.hotels_df is not None else 0,
            "unique_cities": self.hotels_df[
                'normalized_location'].nunique() if self.hotels_df is not None and 'normalized_location' in self.hotels_df.columns else 0,
            "clusters": self.hotels_df[
                'cluster'].nunique() if self.hotels_df is not None and 'cluster' in self.hotels_df.columns else 0
        }, 200

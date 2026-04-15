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


class RestaurantService:
    def __init__(self):
        self.restaurants_df = None
        self.tfidf_vectorizer = None
        self.tfidf_matrix = None
        self.kmeans_model = None
        self.location_mapping = self.create_location_mapping()
        self.load_restaurants()

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
            'jbeil': 'Byblos',
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
            'ghazir': 'Ghazir',
            'jbeil': 'Byblos',
            'baabda': 'Baabda',
            'hadath': 'Hadath',
            'hazmieh': 'Hazmieh',
            'sin el fil': 'Sin El Fil',
            'dora': 'Dora',
            'jal el dib': 'Jal El Dib',
            'antelias': 'Antelias',
            'nafasakh': 'Nafasakh',
            'naqash': 'Naqash',
            'dbayeh': 'Dbayeh',
            'dbaye': 'Dbayeh',
            'naccache': 'Naccache',
            'beirut international airport': 'Beirut',
            'beirut airport': 'Beirut',
            'beirut digital district': 'Beirut',
            'beirut souks': 'Beirut',
            'zaitunay bay': 'Beirut',
            'waterfront': 'Beirut',
            'beirut waterfront': 'Beirut',
            'colonel reef': 'Batroun',
            'mzaar': 'Keserwan',
            'faraya': 'Keserwan',
            'kfardebian': 'Keserwan'
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

    def extract_keywords(self, text, top_n=5):
        """Extract top keywords from text with better filtering"""
        if not text or pd.isna(text):
            return []

        words = str(text).lower().split()

        # Enhanced stopwords list
        stopwords = {
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'be',
            'been', 'has', 'have', 'had', 'restaurant', 'cafe', 'bar', 'food',
            'service', 'located', 'offers', 'serves', 'special', 'fresh', 'daily'
        }

        words = [w for w in words if w not in stopwords and len(w) > 2]

        # Get word frequencies
        word_freq = Counter(words)

        # Extract keywords with context
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

    def parse_opening_hours(self, hours_text):
        """Parse opening hours to determine if restaurant is currently open"""
        if not hours_text or pd.isna(hours_text):
            return False, "Hours not available"

        try:
            hours_text = str(hours_text).lower()
            current_time = datetime.now()
            current_day = current_time.strftime("%A").lower()

            # Check if open 24/7
            if '24/7' in hours_text or '24 hours' in hours_text or 'daily 24' in hours_text:
                return True, "Open 24/7"

            # Simple check for now - can be enhanced with proper time parsing
            if 'closed' in hours_text and current_day in hours_text:
                if 'closed' in hours_text.split(current_day)[1][:50]:
                    return False, "Closed today"

            return True, "Check hours for details"
        except:
            return False, "Hours parsing error"

    def calculate_popularity_score(self, df):
        """Calculate enhanced popularity score based on multiple factors"""
        # Base popularity from rating
        rating_weight = df['rating'] / 5.0

        # Location popularity based on normalized location
        if 'normalized_location' in df.columns:
            location_boost = df['normalized_location'].map(
                df['normalized_location'].value_counts(normalize=True)).fillna(0.5)
        else:
            location_boost = df['location'].map(df['location'].value_counts(normalize=True)).fillna(0.5)

        # Combined popularity score
        popularity = (rating_weight * 0.7 + location_boost * 0.3)

        return popularity.clip(0, 5)

    def create_feature_soup(self, row):
        """Create weighted feature soup for similarity calculation"""
        # Clean all text fields
        food_type = self.clean_text(row.get("food_type", ""))
        restaurant_name = self.clean_text(row.get("restaurant_name", ""))
        tags = self.clean_text(row.get("tags", ""))
        location = self.clean_text(row.get("normalized_location", row.get("location", "")))
        description = self.clean_text(row.get("description", ""))
        restaurant_type = self.clean_text(row.get("restaurant_type", ""))

        # Create weighted components
        components = []

        # Primary features (highest weight)
        if food_type:
            components.extend([food_type] * 5)
        if tags:
            components.extend([tags] * 4)

        # Secondary features
        if restaurant_name:
            components.extend([restaurant_name] * 3)
        if restaurant_type:
            components.extend([restaurant_type] * 3)

        # Tertiary features
        if location:
            components.extend([location] * 2)
        if description:
            components.extend([description] * 2)

        return " ".join(components)

    def load_restaurants(self):
        """Load restaurants from MySQL with enhanced features"""
        try:
            print("Loading restaurants from database...")

            # Establish connection
            conn = get_connection()

            # Execute query and fetch data
            cursor = conn.cursor()
            try:
                cursor.execute("SELECT * FROM restaurants")
                data = cursor.fetchall()
                columns = [desc[0] for desc in cursor.description]
            finally:
                cursor.close()

            conn.close()

            if not data:
                print("No restaurants found")
                self.restaurants_df = pd.DataFrame()
                return

            # Convert to DataFrame
            df = pd.DataFrame(data, columns=columns)
            print(f"Raw data loaded: {len(df)} rows")

            # Preserve display fields; text is cleaned later where feature engineering needs it.
            text_columns = ['restaurant_name', 'restaurant_type', 'tags',
                            'location', 'description', 'food_type', 'price_tier']
            for col in text_columns:
                if col in df.columns:
                    df[col] = df[col].fillna("")

            # Add normalized location (city/district level)
            df['normalized_location'] = df['location'].apply(self.normalize_location)
            df['normalized_location_clean'] = df['normalized_location'].apply(self.clean_text)

            # Convert rating to numeric
            df["rating"] = pd.to_numeric(df["rating"], errors="coerce").fillna(3.0)
            df["rating"] = df["rating"].clip(0, 5)

            # Parse opening hours
            if 'opening_hours' in df.columns:
                df[['is_open', 'hours_status']] = df['opening_hours'].apply(
                    lambda x: pd.Series(self.parse_opening_hours(x))
                )
            else:
                df['is_open'] = True
                df['hours_status'] = 'Hours not available'

            # Calculate popularity score
            df["popularity"] = self.calculate_popularity_score(df)

            # Create feature soup for similarity
            df["ai_features"] = df.apply(self.create_feature_soup, axis=1)

            # Extract keywords
            df["keywords"] = df.apply(
                lambda row: self.extract_keywords(
                    f"{row.get('food_type', '')} {row.get('tags', '')} {row.get('description', '')}",
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
                    X = self.tfidf_vectorizer.fit_transform(df["ai_features"])

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

            self.restaurants_df = df
            print(f"Loaded restaurants: {len(self.restaurants_df)}")
            print(f"Unique normalized locations: {df['normalized_location'].nunique()}")
            print(f"Location distribution: {df['normalized_location'].value_counts().head(10).to_dict()}")
            print(f"Clusters created: {df['cluster'].nunique() if 'cluster' in df.columns else 0}")

        except Exception as e:
            print(f"Database load error: {e}")
            self.restaurants_df = pd.DataFrame()
            self.tfidf_matrix = None

    def get_diverse_recommendations(self, df, n=40):
        """Get diverse recommendations by sampling from different clusters"""
        if 'cluster' not in df.columns or df['cluster'].nunique() <= 1:
            return df.sort_values(by='final_score', ascending=False).head(n)

        result = pd.DataFrame()
        clusters = df['cluster'].unique()

        # Calculate how many to take from each cluster (with priority to clusters with higher scores)
        per_cluster = max(1, n // len(clusters))

        for cluster in clusters:
            cluster_df = df[df['cluster'] == cluster].sort_values(by='final_score', ascending=False)
            take_n = min(per_cluster, len(cluster_df))
            result = pd.concat([result, cluster_df.head(take_n)])

        # If we need more, fill with highest scoring remaining
        if len(result) < n:
            remaining = df[~df.index.isin(result.index)].sort_values(by='final_score', ascending=False)
            result = pd.concat([result, remaining.head(n - len(result))])

        return result.head(n)

    def prepare_output(self, df):
        """Prepare DataFrame for JSON output with proper serialization"""
        output_dict = df.to_dict(orient="records")

        # Convert any non-serializable types to strings
        for item in output_dict:
            for key, value in list(item.items()):
                if hasattr(value, 'isoformat'):  # Handle datetime objects
                    item[key] = value.isoformat() if value else None
                elif pd.isna(value):  # Handle NaN values
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
            # Fit on all restaurant features
            self.tfidf_vectorizer.fit(self.restaurants_df["ai_features"])

        return self.tfidf_vectorizer.transform([user_persona])

    def recommend(self, params):
        """Enhanced recommendation with better accuracy and location mapping"""
        if self.restaurants_df is None or len(self.restaurants_df) == 0:
            return {"error": "No restaurants data available"}, 500

        df = self.restaurants_df.copy()

        # Get user preferences with defaults
        u_food = self.clean_text(params.get("food_type", ""))
        u_loc = self.clean_text(params.get("location", ""))
        u_tags = self.clean_text(params.get("tags", ""))
        u_vibe = self.clean_text(params.get("restaurant_type", ""))
        u_price = params.get("price_tier", "")
        rating_min = float(params.get("rating") or 0)

        # Normalize user location input
        if u_loc:
            # Check if user input is a neighborhood and map to city
            normalized_user_loc = self.normalize_location(u_loc)
            print(f"User location input: '{u_loc}' normalized to: '{normalized_user_loc}'")
        else:
            normalized_user_loc = None

        # Get recommendation type
        rec_type = params.get("recommendation_type", "smart")

        # Create enhanced user persona with normalized location
        user_persona = f"{u_food} {u_food} {u_tags} {u_vibe}".strip()
        if normalized_user_loc:
            user_persona += f" {normalized_user_loc}"

        # If no search criteria, return top 40 with variety
        if not user_persona:
            top_rated = df.nlargest(20, 'rating')
            top_popular = df.nlargest(20, 'popularity')

            # Combine and remove duplicates
            results = pd.concat([top_rated, top_popular]).drop_duplicates(subset=['id']).head(40)

            # Add diversity by shuffling
            results = results.sample(frac=1).reset_index(drop=True)

            # Remove internal columns
            results = results.drop(columns=["ai_features", "keywords", "cluster"], errors="ignore")

            output_dict = self.prepare_output(results)
            return output_dict, 200

        # Calculate similarity
        user_vector = self.calculate_user_preference_vector(user_persona)

        if SKLEARN_AVAILABLE:
            # Use cached TF-IDF matrix if available and matches current data
            if self.tfidf_matrix is not None and self.tfidf_matrix.shape[0] == len(df):
                similarity = cosine_similarity(user_vector, self.tfidf_matrix).flatten()
            else:
                # Recalculate if necessary
                tfidf = TfidfVectorizer(
                    stop_words="english",
                    ngram_range=(1, 3),
                    min_df=1,
                    max_features=9000,
                    sublinear_tf=True
                )
                tfidf_matrix = tfidf.fit_transform(df["ai_features"])
                similarity = cosine_similarity(user_vector, tfidf_matrix).flatten()
        else:
            similarity = df["ai_features"].apply(
                lambda text: self.simple_text_similarity(user_persona, text)
            ).to_numpy()

        df["similarity_score"] = similarity

        # Normalize scores
        df["rating_norm"] = df["rating"] / 5
        df["popularity_norm"] = df["popularity"] / 5

        # Apply filters FIRST to reduce dataset
        filtered_df = df.copy()

        # Apply location filter with normalized matching
        if normalized_user_loc and normalized_user_loc != "Unknown":
            # Filter by normalized location (city level)
            location_mask = (
                    filtered_df['normalized_location'].str.contains(normalized_user_loc, case=False, na=False) |
                    filtered_df['location'].str.contains(u_loc, case=False, na=False)
            )
            filtered_df = filtered_df[location_mask]
            print(f"Location filter '{normalized_user_loc}' matched {len(filtered_df)} restaurants")

        # Apply price filter
        if u_price and u_price.strip():
            filtered_df = filtered_df[
                filtered_df["price_tier"].str.contains(u_price, case=False, na=False)
            ]
            print(f"Price filter '{u_price}' matched {len(filtered_df)} restaurants")

        # Apply rating filter
        if rating_min > 0:
            filtered_df = filtered_df[filtered_df["rating"] >= rating_min]
            print(f"Rating filter >= {rating_min} matched {len(filtered_df)} restaurants")

        # If location filter removed all results, fallback to original with location boost
        if len(filtered_df) == 0 and normalized_user_loc:
            print("No results with location filter, falling back to similarity only")
            filtered_df = df.copy()
            # Apply other filters without location
            if u_price and u_price.strip():
                filtered_df = filtered_df[
                    filtered_df["price_tier"].str.contains(u_price, case=False, na=False)
                ]
            if rating_min > 0:
                filtered_df = filtered_df[filtered_df["rating"] >= rating_min]

        # If still no results, use all data
        if len(filtered_df) == 0:
            filtered_df = df.copy()
            print("No results with filters, using all restaurants")

        # Calculate enhanced scoring
        filtered_df["final_score"] = (
                filtered_df["similarity_score"] * 0.55 +
                filtered_df["rating_norm"] * 0.25 +
                filtered_df["popularity_norm"] * 0.20
        )

        # Boost for location match if location was specified
        if normalized_user_loc and normalized_user_loc != "Unknown":
            location_boost = filtered_df['normalized_location'].apply(
                lambda x: 0.2 if x == normalized_user_loc else 0.0
            )
            filtered_df["final_score"] += location_boost

        # Sort and limit results
        if rec_type == "diverse" and len(filtered_df) > 20:
            final_output = self.get_diverse_recommendations(filtered_df, n=40)
        else:
            final_output = filtered_df.nlargest(40, 'final_score')

        # Add metadata for debugging/display
        final_output['similarity_percentage'] = (final_output['similarity_score'] * 100).round(1)
        final_output['recommendation_score'] = final_output['final_score'].round(3)

        # Remove internal columns
        columns_to_drop = ["ai_features", "keywords", "cluster", "final_score",
                           "rating_norm", "popularity_norm", "similarity_score"]
        final_output = final_output.drop(columns=[col for col in columns_to_drop
                                                  if col in final_output.columns],
                                         errors='ignore')

        print(f"Final recommendations: {final_output}")

        # Prepare output
        recommendations_list = self.prepare_output(final_output)

        response_dict = {
            'recommendations': recommendations_list,
            'metadata': {
                'user_location_normalized': normalized_user_loc if normalized_user_loc else 'None',
                'total_matching': len(final_output),
                'avg_similarity': f"{final_output['similarity_percentage'].mean():.1f}%",
                'min_similarity': f"{final_output['similarity_percentage'].min():.1f}%",
                'max_similarity': f"{final_output['similarity_percentage'].max():.1f}%",
                'total_recommendations': len(final_output),
                'recommendation_type': rec_type
            }
        }
        return response_dict, 200

    def get_similar_restaurants(self, restaurant_id, limit=6):
        """Enhanced similar restaurants with location context"""
        if self.restaurants_df is None or len(self.restaurants_df) == 0:
            return {"error": "No restaurants data available"}, 500

        df = self.restaurants_df.copy()

        # Find the target restaurant
        target_restaurant = df[df['id'] == restaurant_id]

        if len(target_restaurant) == 0:
            return {"error": f"Restaurant with ID {restaurant_id} not found"}, 404

        # Extract target restaurant features
        target_idx = target_restaurant.index[0]
        target_food_type = target_restaurant['food_type'].values[0] if 'food_type' in target_restaurant.columns else ''
        target_location = target_restaurant['normalized_location'].values[
            0] if 'normalized_location' in target_restaurant.columns else ''
        target_price_tier = target_restaurant['price_tier'].values[
            0] if 'price_tier' in target_restaurant.columns else ''
        target_tags = target_restaurant['tags'].values[0] if 'tags' in target_restaurant.columns else ''
        target_cluster = target_restaurant['cluster'].values[0] if 'cluster' in target_restaurant.columns else None

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
                tfidf_matrix = tfidf.fit_transform(df["ai_features"])
                target_vector = tfidf_matrix[target_idx]
                similarities = cosine_similarity(target_vector, tfidf_matrix).flatten()
        else:
            target_text = target_restaurant["ai_features"].values[0]
            similarities = df["ai_features"].apply(
                lambda text: self.simple_text_similarity(target_text, text)
            ).to_numpy()

        df['similarity_score'] = similarities

        # Remove target restaurant
        similar_df = df[df['id'] != restaurant_id].copy()

        # Enhanced similarity scoring with multiple factors
        similar_df['food_type_match'] = similar_df['food_type'].apply(
            lambda x: 1.0 if x == target_food_type
            else 0.6 if target_food_type in str(x) or str(x) in target_food_type
            else 0.0
        )

        similar_df['tag_match'] = similar_df['tags'].apply(
            lambda x: self.calculate_tag_similarity(target_tags, str(x))
        )

        similar_df['location_match'] = similar_df['normalized_location'].apply(
            lambda x: 1.0 if x == target_location
            else 0.3 if target_location in str(x) or str(x) in target_location
            else 0.0
        )


        # Calculate final similarity score with weights
        similar_df['final_similarity'] = (
                similar_df['similarity_score'] * 0.45 +
                similar_df['food_type_match'] * 0.25 +
                similar_df['tag_match'] * 0.15 +
                similar_df['location_match'] * 0.15
        )

        # Boost same-cluster restaurants
        if target_cluster is not None and 'cluster' in similar_df.columns:
            similar_df['cluster_boost'] = similar_df['cluster'].apply(
                lambda x: 0.1 if x == target_cluster else 0
            )
            similar_df['final_similarity'] += similar_df['cluster_boost']

        # Get top similar restaurants
        similar_restaurants = similar_df.nlargest(limit, 'final_similarity')

        # Remove internal columns
        result_df = similar_restaurants.drop(
            columns=['ai_features', 'keywords', 'similarity_score',
                     'food_type_match', 'tag_match', 'location_match', 'price_match',
                     'final_similarity', 'rating_norm', 'popularity_norm', 'cluster_boost'],
            errors='ignore'
        )

        if 'cluster' in result_df.columns:
            result_df = result_df.drop(columns=['cluster'], errors='ignore')

        output_dict = self.prepare_output(result_df)

        return {
            'restaurant_id': restaurant_id,
            'restaurant_name': target_restaurant['restaurant_name'].values[0],
            'restaurant_location': target_location,
            'count': len(output_dict),
            'similar_restaurants': output_dict
        }, 200

    def calculate_tag_similarity(self, tags1, tags2):
        """Calculate similarity between tag strings"""
        if not tags1 or not tags2:
            return 0.0

        tags1_set = set(tags1.lower().split(','))
        tags2_set = set(tags2.lower().split(','))

        if not tags1_set or not tags2_set:
            return 0.0

        intersection = len(tags1_set & tags2_set)
        union = len(tags1_set | tags2_set)

        return intersection / union if union > 0 else 0.0

    def get_stats(self):
        """Get comprehensive restaurant statistics"""
        if self.restaurants_df is None or len(self.restaurants_df) == 0:
            return {"error": "No data"}, 500

        stats = {
            "total_restaurants": len(self.restaurants_df),
            "avg_rating": float(self.restaurants_df["rating"].mean()),
            "rating_distribution": self.restaurants_df["rating"].value_counts().sort_index().to_dict(),
            "top_locations_by_city": self.restaurants_df["normalized_location"].value_counts().head(10).to_dict(),
            "top_neighborhoods": self.restaurants_df["location"].value_counts().head(10).to_dict(),
            "top_food_types": self.restaurants_df["food_type"].value_counts().head(10).to_dict(),
            "price_tier_distribution": self.restaurants_df["price_tier"].value_counts().to_dict(),
            "open_restaurants": int(
                self.restaurants_df["is_open"].sum()) if 'is_open' in self.restaurants_df.columns else 0,
            "avg_popularity": float(self.restaurants_df["popularity"].mean()),
            "unique_cuisines": self.restaurants_df["food_type"].nunique(),
            "unique_cities": self.restaurants_df["normalized_location"].nunique(),
            "unique_neighborhoods": self.restaurants_df["location"].nunique()
        }

        return stats, 200

    def reload(self):
        """Reload restaurants data and refresh models"""
        self.location_mapping = self.create_location_mapping()
        self.load_restaurants()
        return {
            "message": "Restaurants reloaded successfully",
            "count": len(self.restaurants_df) if self.restaurants_df is not None else 0,
            "unique_cities": self.restaurants_df[
                'normalized_location'].nunique() if self.restaurants_df is not None and 'normalized_location' in self.restaurants_df.columns else 0,
            "clusters": self.restaurants_df[
                'cluster'].nunique() if self.restaurants_df is not None and 'cluster' in self.restaurants_df.columns else 0
        }, 200

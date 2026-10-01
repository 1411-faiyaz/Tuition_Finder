import pandas as pd
import joblib

from sklearn.model_selection import train_test_split
from sklearn.compose import ColumnTransformer
from sklearn.preprocessing import OneHotEncoder
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from catboost import CatBoostRegressor


# ==========================================
# 1. Load Dataset
# ==========================================

df = pd.read_csv("data/tuition_salary_data_5000.csv")

print("Dataset shape:", df.shape)
print(df.head())


# ==========================================
# 2. Select Features and Target
# ==========================================

features = [
    "place",
    "class",
    "version",
    "subject",
    "days_per_week",
    "hours_per_day"
]

target = "salary"

X = df[features]
y = df[target]


# ==========================================
# 3. Train/Test Split
# ==========================================

X_train, X_test, y_train, y_test = train_test_split(
    X,
    y,
    test_size=0.20,
    random_state=42
)

print("Training data:", X_train.shape)
print("Testing data:", X_test.shape)


# ==========================================
# 4. Categorical Columns
# ==========================================

categorical_features = [
    "place",
    "version",
    "subject"
]


# ==========================================
# 5. Train CatBoost
# ==========================================

model = CatBoostRegressor(
    iterations=500,
    depth=7,
    learning_rate=0.05,
    loss_function="RMSE",
    verbose=100,
    random_seed=42
)

model.fit(
    X_train,
    y_train,
    cat_features=categorical_features
)


# ==========================================
# 6. Prediction
# ==========================================

predictions = model.predict(X_test)


# ==========================================
# 7. Evaluation
# ==========================================

mae = mean_absolute_error(y_test, predictions)
rmse = mean_squared_error(y_test, predictions) ** 0.5
r2 = r2_score(y_test, predictions)

print("\n========== MODEL PERFORMANCE ==========")
print("MAE :", round(mae, 2))
print("RMSE:", round(rmse, 2))
print("R2  :", round(r2, 4))


# ==========================================
# 8. Save Model
# ==========================================

joblib.dump(model, "model/salary_model.pkl")

print("\nModel saved successfully!")
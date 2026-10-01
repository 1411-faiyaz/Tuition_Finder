import logging
import os
from typing import Literal

import joblib
from fastapi import FastAPI, Header, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

# ==========================================
# Logging (server-side only -- never shown to the client)
# ==========================================

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("salary-api")

# ==========================================
# FastAPI App
# ==========================================

app = FastAPI(title="Tuition Finder - Salary Prediction API")


# ==========================================
# CORS
# ==========================================
# The browser never calls this service directly -- the PHP app
# (tutor/predict-salary.php) proxies requests to it server-to-server.
# CORS is kept narrow; override with the ALLOWED_ORIGINS env var
# (comma-separated) if you ever need to call this API from a browser
# during local development.

_default_origins = "http://localhost,http://127.0.0.1"
allowed_origins = [
    o.strip()
    for o in os.environ.get("ALLOWED_ORIGINS", _default_origins).split(",")
    if o.strip()
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins,
    allow_credentials=True,
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)


# ==========================================
# Shared-secret auth
# ==========================================
# This is a private microservice, not meant to be reachable by end
# users at all. The PHP layer (config/backend.php) holds this same
# key server-side and sends it on every request, so a guardian/student
# can't get a prediction even if they discover this service's URL and
# call it directly -- only the Tuition Finder PHP backend can.
#
# Change this value (and the matching one in config/backend.php)
# before deploying anywhere beyond your own machine. In production,
# set it via the SALARY_API_KEY environment variable instead of
# editing the source.

API_KEY = os.environ.get("SALARY_API_KEY", "tf_salary_bot_2026_change_me")


def verify_api_key(x_api_key: str = Header(default=None)):
    if not x_api_key or x_api_key != API_KEY:
        raise HTTPException(status_code=401, detail="Unauthorized")


# ==========================================
# Load ML Model
# ==========================================
# Fails fast and loudly at startup (rather than on the first request)
# if the trained model file is missing or corrupted.

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model", "salary_model.pkl")

try:
    model = joblib.load(MODEL_PATH)
    logger.info("Salary model loaded from %s", MODEL_PATH)
except Exception:
    logger.exception("Failed to load salary model from %s", MODEL_PATH)
    raise


# ==========================================
# Request / Response Models
# ==========================================
# The allowed values below mirror the categories the model was
# actually trained on (backend/data/tuition_salary_data_5000.csv).
# Keep this list and tutor/predict-salary.php's allow-lists in sync
# if the training data ever changes.

Place = Literal[
    "Badda", "Banani", "Gulshan", "Khilkhet", "Middle Badda", "Mirpur",
    "Mohakhali", "New Market", "Notun Bazar", "Rampura", "Shahbag", "Uttara",
]
Version = Literal["Bangla", "English Medium", "English Version"]
Subject = Literal[
    "All Subjects", "Bangla", "Biology", "Chemistry", "English",
    "General Science", "ICT", "Math", "Physics",
]


class SalaryRequest(BaseModel):
    place: Place
    class_number: int = Field(ge=1, le=12)
    version: Version
    subject: Subject
    days_per_week: int = Field(ge=1, le=7)
    hours_per_day: float = Field(ge=0.5, le=6)


class SalaryResponse(BaseModel):
    predicted_salary: int


# ==========================================
# Prediction API
# ==========================================

@app.post("/predict-salary", response_model=SalaryResponse)
def predict_salary(data: SalaryRequest, x_api_key: str = Header(default=None)):
    verify_api_key(x_api_key)

    input_data = [[
        data.place,
        data.class_number,
        data.version,
        data.subject,
        data.days_per_week,
        data.hours_per_day,
    ]]

    try:
        prediction = model.predict(input_data)[0]
    except Exception:
        logger.exception("Model prediction failed for input: %s", data)
        raise HTTPException(
            status_code=500,
            detail="Could not generate a prediction right now.",
        )

    prediction = round(float(prediction) / 500) * 500
    return SalaryResponse(predicted_salary=int(prediction))


# ==========================================
# Health check (used to confirm the service is up; no API key required
# so it can be checked quickly without secrets -- it reveals nothing
# sensitive)
# ==========================================

@app.get("/health")
def health():
    return {"status": "ok"}

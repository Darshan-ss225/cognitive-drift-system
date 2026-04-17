from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route("/")
def home():
    return "Cognitive Drift AI Server Running"

@app.route("/health")
def health():
    return jsonify({
        "status": "ok",
        "message": "AI server is running"
    })

@app.route("/analyze", methods=["POST"])
def analyze():
    data = request.get_json(force=True) or {}

    reaction_avg = float(data.get("reaction_avg", 0))
    confidence_score = float(data.get("confidence_score", 0))
    quiz_score = float(data.get("quiz_score", 0))

    drift_score = 0.0

    if reaction_avg > 2.0:
        drift_score += 0.30
    elif reaction_avg > 1.5:
        drift_score += 0.18
    else:
        drift_score += 0.08

    if confidence_score < 60:
        drift_score += 0.25
    elif confidence_score < 75:
        drift_score += 0.12
    else:
        drift_score += 0.05

    if quiz_score < 60:
        drift_score += 0.25
    elif quiz_score < 75:
        drift_score += 0.12
    else:
        drift_score += 0.05

    drift_score = round(min(drift_score, 1.0), 2)

    if drift_score >= 0.60:
        drift_status = "High Drift"
        risk_level = "High"
        ai_summary = "Significant cognitive deviation detected."
    elif drift_score >= 0.30:
        drift_status = "Moderate Drift"
        risk_level = "Medium"
        ai_summary = "Noticeable behavioral shift detected."
    else:
        drift_status = "Low Drift"
        risk_level = "Low"
        ai_summary = "Behavior remains close to baseline."

    return jsonify({
        "drift_score": drift_score,
        "drift_status": drift_status,
        "risk_level": risk_level,
        "ai_summary": ai_summary
    })

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
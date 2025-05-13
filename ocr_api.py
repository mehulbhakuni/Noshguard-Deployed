from flask import Flask, request, jsonify
from flask_cors import CORS
from pathlib import Path
import base64
import json
import os

# Use 'Mistral' from the main package as per the migration guide's "New" example
# and assuming mistralai library v1.7.0+ is installed.
from mistralai import Mistral

app = Flask(__name__)
CORS(app)

# --- API KEY SECTION ---
# IMPORTANT: Replace "YOUR_ACTUAL_MISTRAL_API_KEY" with your real API key
# Or, set the MISTRAL_API_KEY environment variable.
api_key = os.environ.get("MISTRAL_API_KEY")
if not api_key:
    api_key = "aCHLMXr7v1Ojf2lniUNkjftHGnzxLoDp" # YOUR ACTUAL API KEY HERE
    print(f"INFO: Using hardcoded MISTRAL_API_KEY. Key ends with: ...{api_key[-4:] if len(api_key) > 3 else 'KEY_TOO_SHORT'}")
    if api_key == "YOUR_ACTUAL_MISTRAL_API_KEY": # Simple check if placeholder is still there
        print("WARNING: Placeholder API key is being used. OCR/Analyze will fail.")
# --- END OF API KEY SECTION ---

try:
    client = Mistral(api_key=api_key)
    print("INFO: Mistral client initialized successfully.")
except Exception as e:
    print(f"CRITICAL ERROR: Failed to initialize Mistral client: {e}")
    # If the client doesn't initialize, the app can't function.
    # Consider exiting or having a fallback mode if appropriate.
    client = None # Ensure client is None if initialization fails

@app.route('/ocr', methods=['POST'])
def run_ocr():
    if not client: # Check if client was initialized
        return jsonify({"error": "Mistral client not initialized. Check API key and server logs."}), 503

    try:
        if 'image' not in request.files:
            return jsonify({"error": "No image file provided in 'image' field"}), 400
        file = request.files["image"]
        if file.filename == '':
            return jsonify({"error": "No image selected for uploading"}), 400

        # Using a temporary in-memory buffer might be better for production
        # but for simplicity with Path and ensuring file reading:
        temp_image_path = Path("temp_uploaded_image.jpg") # Ensure this directory is writable
        file.save(temp_image_path)
        with open(temp_image_path, "rb") as image_file:
            encoded_image_string = base64.b64encode(image_file.read()).decode('utf-8')
        os.remove(temp_image_path) # Clean up the temporary file
        
        base64_data_url = f"data:{file.mimetype};base64,{encoded_image_string}"

        messages_for_ocr = [
            {
                "role": "user",
                "content": [
                    {"type": "image_url", "image_url": {"url": base64_data_url}},
                    {"type": "text", "text": "Extract all text from this image. Present it clearly and concisely as a single block of text."}
                ]
            }
        ]
        
        # Using client.chat.completions.create based on common OpenAI-compatible client patterns
        # and if client.chat.complete() was not found.
        ocr_response = client.chat.completions.create(
            model="mistral-small-latest", # Changed to mistral-small-latest for vision capability
            messages=messages_for_ocr
        )
        # Check if response and choices are as expected
        if ocr_response.choices and len(ocr_response.choices) > 0:
            extracted_text = ocr_response.choices[0].message.content.strip()
        else:
            app.logger.error("OCR response format unexpected or empty choices.")
            extracted_text = ""


        if not extracted_text:
            return jsonify({"error": "OCR could not extract text from the image or response was empty."}), 400
        return jsonify({"ocr_text": extracted_text})

    except Exception as e:
        app.logger.error(f"OCR Error: {type(e).__name__} - {str(e)}")
        return jsonify({"error": f"An internal error occurred during OCR processing: {str(e)}"}), 500

@app.route('/analyze', methods=['POST'])
def analyze_health_risk():
    if not client: # Check if client was initialized
        return jsonify({"error": "Mistral client not initialized. Check API key and server logs."}), 503

    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON data provided"}), 400
        ocr_text = data.get("ocr_text", "")
        user_diseases = data.get("user_diseases", [])

        if not ocr_text:
            return jsonify({"error": "Missing 'ocr_text' in input"}), 400
        if not isinstance(user_diseases, list):
             return jsonify({"error": "'user_diseases' must be a list"}), 400

        diseases_string = ", ".join(user_diseases) if user_diseases else "no specific medical conditions stated by the user"
        
        prompt_text_content = f"""As NoshGuard, a helpful food ingredient analyzer:
The user reports these conditions: {diseases_string}.
The scanned product ingredients are:
---
{ocr_text}
---
Analyze potential risks based ONLY on these ingredients and the user's stated conditions.
If risks exist, explain them simply. If no obvious risks for their conditions are found from these ingredients, clearly state that.
Conclude with a general suitability remark (e.g., "This product appears generally suitable," "Caution is advised," "This product may not be suitable").
**IMPORTANT: Always end your response with the exact sentence: 'This is not medical advice. Consult a doctor or nutritionist for personalized guidance.'**
Keep the entire analysis concise and easy to read, preferably under 100 words. Do not use markdown formatting like ## or **.
"""
        
        messages_for_analysis = [
            {"role": "user", "content": prompt_text_content}
        ]

        response = client.chat.completions.create(
            model="mistral-small-latest", # This model is good for text analysis
            messages=messages_for_analysis,
            temperature=0.2 # Lower temperature for more factual, less creative advice
        )
        
        if response.choices and len(response.choices) > 0:
            advice = response.choices[0].message.content.strip()
        else:
            app.logger.error("Analysis response format unexpected or empty choices.")
            advice = "Could not retrieve analysis at this time."

        return jsonify({"advice": advice})

    except Exception as e:
        app.logger.error(f"Analysis Error: {type(e).__name__} - {str(e)}")
        return jsonify({"error": f"An internal error occurred during analysis: {str(e)}"}), 500

if __name__ == '__main__':
    # For production, use a WSGI server like Gunicorn or Waitress
    # Example: gunicorn --bind 0.0.0.0:5000 ocr_api:app
    app.run(port=5000, debug=True) # Debug=True is for development only
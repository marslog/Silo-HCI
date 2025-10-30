FROM python:3.11-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    curl \
    e2fsprogs \
    util-linux \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements
COPY requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy application
COPY . .

# Create directories
RUN mkdir -p /app/database /app/logs

# Expose port
EXPOSE 5000

# Run with gunicorn - เพิ่ม timeout สำหรับ upload ไฟล์ใหญ่
CMD ["gunicorn", "--bind", "0.0.0.0:5000", "--workers", "4", "--timeout", "7200", "--graceful-timeout", "7200", "wsgi:application"]


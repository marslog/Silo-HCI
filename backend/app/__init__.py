"""
Silo HCI Backend API
Flask application for Proxmox VE management
"""

from flask import Flask, session
from flask_cors import CORS
from app.config import Config
from app.models.user import db
import logging

def create_app(config_class=Config):
    """Application factory pattern"""
    app = Flask(__name__)
    app.config.from_object(config_class)
    
    # Initialize database
    db.init_app(app)
    
    # Enable CORS
    CORS(app, resources={
        r"/api/*": {
            "origins": "*",
            "methods": ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
            "allow_headers": ["Content-Type", "Authorization"]
        }
    }, supports_credentials=True)
    
    # Setup logging
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
    )
    
    # Register blueprints
    from app.api.v1 import nodes, qemu, lxc, storage, network, backup, cluster, monitoring, auth, system, totp
    
    app.register_blueprint(auth.bp)
    app.register_blueprint(totp.bp)
    app.register_blueprint(system.bp)
    app.register_blueprint(nodes.bp, url_prefix='/api/v1')
    app.register_blueprint(lxc.bp, url_prefix='/api/v1')
    app.register_blueprint(storage.bp, url_prefix='/api/v1')
    app.register_blueprint(network.bp, url_prefix='/api/v1')
    app.register_blueprint(backup.bp, url_prefix='/api/v1')
    app.register_blueprint(cluster.bp, url_prefix='/api/v1')
    app.register_blueprint(monitoring.bp, url_prefix='/api/v1')
    
    # Create database tables
    with app.app_context():
        db.create_all()
    
    # Health check endpoint
    @app.route('/health')
    def health():
        return {'status': 'healthy', 'service': 'silo-hci-api'}, 200
    
    return app

app = create_app()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)

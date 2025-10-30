"""
Configuration for Silo HCI Backend
"""
import os
import json
from pathlib import Path

class Config:
    """Base configuration"""
    
    # Base directory
    BASE_DIR = Path(__file__).resolve().parent.parent
    
    # Flask
    SECRET_KEY = os.getenv('SECRET_KEY', 'dev-secret-key-change-in-production')
    DEBUG = os.getenv('APP_DEBUG', 'False').lower() == 'true'
    
    # API
    API_PREFIX = os.getenv('API_PREFIX', '/api/v1')
    
    # Database
    DB_PATH = BASE_DIR / 'database' / 'silo.db'
    SQLALCHEMY_DATABASE_URI = f'sqlite:///{DB_PATH}'
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    
    # Cache
    CACHE_ENABLED = os.getenv('CACHE_ENABLED', 'true').lower() == 'true'
    CACHE_TTL = int(os.getenv('CACHE_TTL', '60'))
    
    # Offline Mode
    OFFLINE_MODE = os.getenv('OFFLINE_MODE', 'true').lower() == 'true'
    SYNC_INTERVAL = int(os.getenv('SYNC_INTERVAL', '300'))
    
    # Proxmox Configuration
    @staticmethod
    def get_proxmox_config():
        """Load Proxmox configuration from file"""
        config_path = Config.BASE_DIR / 'config' / 'proxmox.json'
        
        if config_path.exists():
            with open(config_path, 'r') as f:
                return json.load(f)
        
        # Fallback to environment variables
        return {
            'proxmox': {
                'host': os.getenv('PROXMOX_HOST', 'localhost'),
                'port': int(os.getenv('PROXMOX_PORT', '8006')),
                'user': os.getenv('PROXMOX_USER', 'root@pam'),
                'password': os.getenv('PROXMOX_PASSWORD', ''),
                'token_name': os.getenv('PROXMOX_TOKEN_NAME'),
                'token_value': os.getenv('PROXMOX_TOKEN_VALUE'),
                'verify_ssl': os.getenv('PROXMOX_VERIFY_SSL', 'false').lower() == 'true'
            },
            'cache': {
                'enabled': Config.CACHE_ENABLED,
                'ttl': Config.CACHE_TTL
            },
            'offline_mode': {
                'enabled': Config.OFFLINE_MODE,
                'sync_interval': Config.SYNC_INTERVAL
            }
        }

class DevelopmentConfig(Config):
    """Development configuration"""
    DEBUG = True

class ProductionConfig(Config):
    """Production configuration"""
    DEBUG = False

class TestingConfig(Config):
    """Testing configuration"""
    TESTING = True
    DB_PATH = ':memory:'

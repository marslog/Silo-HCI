"""
Proxmox API Service
Wrapper around proxmoxer with caching and offline support
"""
from proxmoxer import ProxmoxAPI
from app.config import Config
import logging
import time

logger = logging.getLogger(__name__)

class ProxmoxService:
    """Proxmox API Service with caching"""
    
    _instance = None
    _proxmox = None
    _cache = {}
    _last_connected = None
    
    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(ProxmoxService, cls).__new__(cls)
        return cls._instance
    
    def __init__(self):
        """Initialize Proxmox connection"""
        if self._proxmox is None:
            self.connect()
    
    def connect(self):
        """Establish connection to Proxmox"""
        try:
            config = Config.get_proxmox_config()
            pve_config = config['proxmox']
            
            # Use API token if available
            if pve_config.get('token_name') and pve_config.get('token_value'):
                self._proxmox = ProxmoxAPI(
                    pve_config['host'],
                    user=pve_config['user'],
                    token_name=pve_config['token_name'],
                    token_value=pve_config['token_value'],
                    port=pve_config.get('port', 8006),
                    verify_ssl=pve_config.get('verify_ssl', False)
                )
            else:
                # Use password authentication
                self._proxmox = ProxmoxAPI(
                    pve_config['host'],
                    user=pve_config['user'],
                    password=pve_config['password'],
                    port=pve_config.get('port', 8006),
                    verify_ssl=pve_config.get('verify_ssl', False)
                )
            
            self._last_connected = time.time()
            logger.info(f"Connected to Proxmox at {pve_config['host']}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to connect to Proxmox: {e}")
            return False
    
    def is_connected(self):
        """Check if connected to Proxmox"""
        if self._proxmox is None:
            return False
        
        try:
            # Test connection with a simple API call
            self._proxmox.version.get()
            return True
        except:
            return False
    
    def get_proxmox(self):
        """Get Proxmox API instance"""
        if not self.is_connected():
            self.connect()
        return self._proxmox
    
    def cache_get(self, key):
        """Get value from cache"""
        if key in self._cache:
            cached_data, timestamp = self._cache[key]
            if time.time() - timestamp < Config.CACHE_TTL:
                return cached_data
        return None
    
    def cache_set(self, key, value):
        """Set value in cache"""
        self._cache[key] = (value, time.time())
    
    def cache_clear(self, pattern=None):
        """Clear cache"""
        if pattern:
            keys_to_delete = [k for k in self._cache.keys() if pattern in k]
            for key in keys_to_delete:
                del self._cache[key]
        else:
            self._cache.clear()

# Global instance
proxmox_service = ProxmoxService()

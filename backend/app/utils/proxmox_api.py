"""
Proxmox API utilities
"""
import requests
import json
from typing import Dict, Any, Optional
import logging

logger = logging.getLogger(__name__)

class ProxmoxAPIClient:
    """Low-level Proxmox API client"""
    
    def __init__(self, host: str, port: int = 8006, verify_ssl: bool = False):
        self.host = host
        self.port = port
        self.verify_ssl = verify_ssl
        self.base_url = f"https://{host}:{port}/api2/json"
        self.ticket = None
        self.csrf_token = None
        self.session = requests.Session()
        
        if not verify_ssl:
            import urllib3
            urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    def login(self, username: str, password: str = None, 
              token_name: str = None, token_value: str = None) -> bool:
        """
        Login to Proxmox API
        
        Args:
            username: Username (e.g., 'root@pam')
            password: Password (for password auth)
            token_name: API token name (for token auth)
            token_value: API token value (for token auth)
        """
        try:
            if token_name and token_value:
                # API Token authentication
                self.session.headers.update({
                    'Authorization': f'PVEAPIToken={username}!{token_name}={token_value}'
                })
                logger.info(f"Logged in with API token: {username}!{token_name}")
                return True
            elif password:
                # Password authentication
                response = self.session.post(
                    f"{self.base_url}/access/ticket",
                    data={'username': username, 'password': password},
                    verify=self.verify_ssl
                )
                
                if response.status_code == 200:
                    data = response.json()['data']
                    self.ticket = data['ticket']
                    self.csrf_token = data['CSRFPreventionToken']
                    
                    self.session.headers.update({
                        'CSRFPreventionToken': self.csrf_token
                    })
                    self.session.cookies.set('PVEAuthCookie', self.ticket)
                    
                    logger.info(f"Logged in successfully: {username}")
                    return True
                else:
                    logger.error(f"Login failed: {response.status_code}")
                    return False
            else:
                logger.error("No authentication method provided")
                return False
                
        except Exception as e:
            logger.error(f"Login error: {e}")
            return False
    
    def request(self, method: str, path: str, data: Optional[Dict] = None) -> Any:
        """
        Make API request
        
        Args:
            method: HTTP method (GET, POST, PUT, DELETE)
            path: API path (e.g., '/nodes')
            data: Request data
        
        Returns:
            Response data
        """
        url = f"{self.base_url}{path}"
        
        try:
            if method.upper() == 'GET':
                response = self.session.get(url, params=data, verify=self.verify_ssl)
            elif method.upper() == 'POST':
                response = self.session.post(url, data=data, verify=self.verify_ssl)
            elif method.upper() == 'PUT':
                response = self.session.put(url, data=data, verify=self.verify_ssl)
            elif method.upper() == 'DELETE':
                response = self.session.delete(url, verify=self.verify_ssl)
            else:
                raise ValueError(f"Unsupported HTTP method: {method}")
            
            if response.status_code in [200, 201]:
                return response.json().get('data')
            else:
                logger.error(f"API request failed: {response.status_code} - {response.text}")
                return None
                
        except Exception as e:
            logger.error(f"Request error: {e}")
            return None
    
    def get(self, path: str, params: Optional[Dict] = None) -> Any:
        """GET request"""
        return self.request('GET', path, params)
    
    def post(self, path: str, data: Optional[Dict] = None) -> Any:
        """POST request"""
        return self.request('POST', path, data)
    
    def put(self, path: str, data: Optional[Dict] = None) -> Any:
        """PUT request"""
        return self.request('PUT', path, data)
    
    def delete(self, path: str) -> Any:
        """DELETE request"""
        return self.request('DELETE', path)

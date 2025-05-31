# ☁️ Cloudflare DynDNS Panel

A modern, secure, and feature-rich Dynamic DNS management panel for Cloudflare. Automatically update your DNS records when your IP address changes, with a beautiful web interface and powerful automation features.

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![Cloudflare](https://img.shields.io/badge/Cloudflare-API-orange)

## ✨ Features

### 🔐 **Security First**
- **Secure password hashing** with PHP's `password_hash()`
- **CSRF protection** on all forms
- **Session-based authentication**
- **API token validation**
- **XSS protection** with input sanitization

### 🌐 **Multi-Domain Management**
- **Multiple domains** support
- **Individual proxy settings** per domain
- **Enable/disable domains** without deletion
- **Bulk operations** for all domains

### 🔍 **Smart Domain Discovery**
- **Automatic domain browser** - no manual ID lookup needed
- **One-click domain addition** from Cloudflare API
- **Real-time A-record display**
- **Proxy status detection**

### ⏰ **Flexible Automation**
- **Configurable update intervals**:
  - Manual only
  - Every 5, 15, 30 minutes
  - Every 1, 6, 12 hours
  - Daily updates
- **Automatic cron job generation**
- **IP change detection** (only updates when needed)
- **Comprehensive logging**

### 📊 **Advanced Monitoring**
- **Detailed update logs** with pagination
- **Success/failure statistics**
- **Automatic vs manual update tracking**
- **Error reporting** with detailed messages

### 🎨 **Modern Interface**
- **Responsive design** for all devices
- **Clean, intuitive UI**
- **Real-time status updates**
- **Copy-to-clipboard** functionality

## 🚀 Quick Start

### Prerequisites
- **PHP 7.4+** with `curl` and `json` extensions
- **Web server** (Apache, Nginx, etc.)
- **Cloudflare account** with domains
- **Cron access** (for automatic updates)

### Installation

1. **Download and extract** the files to your web directory:
   ```bash
   git clone https://github.com/yourusername/cloudflare-dyndns-panel.git
   cd cloudflare-dyndns-panel
   ```

2. **Set proper permissions**:
   ```bash
   chmod 755 *.php
   chmod 666 config.json  # Will be created automatically
   ```

3. **Access the panel** in your web browser:
   ```
   https://yourdomain.com/path/to/panel/
   ```

4. **First login**:
   - Default password: `changeme`
   - **Change the password immediately** after first login!

## 🔑 Cloudflare API Token Setup

### Creating an API Token

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Click your profile → **"My Profile"**
3. Go to **"API Tokens"** tab
4. Click **"Create Token"**
5. Choose **"Create Custom Token"**

### Required Permissions

```
Permissions:
- Zone:Zone:Read
- Zone:DNS:Edit

Zone Resources:
- Include:All zones (or specific zones)

Client IP Address Filtering:
- (Optional) Your server's IP
```

### Why These Permissions?
- **Zone:Read** - List your domains and find Zone IDs
- **DNS:Edit** - Update A records with new IP addresses
- **No other permissions needed** - Maximum security!

## 📖 Usage Guide

### Initial Setup

1. **Login** with default password `changeme`
2. **Change password** immediately
3. **Add your Cloudflare API token**
4. **Choose update interval** (or keep manual)

### Adding Domains

#### Method 1: Domain Browser (Recommended)
1. Click **"Load My Domains"**
2. Select your domain
3. Click **"Show A Records"**
4. Click **"Add"** next to the record you want to manage

#### Method 2: Manual Entry
1. Find your Zone ID in Cloudflare Dashboard
2. Find your Record ID using browser dev tools or API
3. Enter the IDs manually in the form

### Setting Up Automatic Updates

1. **Choose an interval** in the panel
2. **Copy the generated cron command**
3. **Add to your crontab**:
   ```bash
   crontab -e
   # Paste the copied command
   ```

Example cron command for 15-minute updates:
```bash
*/15 * * * * /usr/bin/php /path/to/your/panel/auto_update.php
```

## 📁 File Structure

```
cloudflare-dyndns-panel/
├── index.php              # Main panel interface
├── update.php              # Manual update script
├── auto_update.php         # Automatic update script (for cron)
├── change_password.php     # Password change interface
├── logs.php               # Update logs viewer
├── api_helper.php         # API communication helper
├── config.json            # Configuration file (auto-created)
├── update_log.json        # Update logs (auto-created)
├── last_ip.txt           # Last known IP (auto-created)
└── README.md              # This file
```

## ⚙️ Configuration

The `config.json` file stores all settings:

```json
{
    "password": "$2y$10$...",           // Hashed password
    "api_token": "your_api_token",      // Cloudflare API token
    "update_interval": "15min",         // Update frequency
    "domains": [                        // Managed domains
        {
            "id": "unique_id",
            "zone_id": "cloudflare_zone_id",
            "record_id": "dns_record_id",
            "name": "dyn.example.com",
            "proxied": true,
            "enabled": true
        }
    ]
}
```

## 🔧 Advanced Configuration

### Custom PHP Path
If PHP is not in `/usr/bin/php`, edit the cron command:
```bash
*/15 * * * * /custom/path/to/php /path/to/auto_update.php
```

### Logging
- **Update logs**: Stored in `update_log.json`
- **Last 100 entries** kept automatically
- **View in panel**: Go to "View Update Logs"

### IP Change Detection
The system only updates DNS when your IP actually changes:
- Current IP stored in `last_ip.txt`
- Saves API calls and reduces logs
- Can be disabled by deleting the file

## 🛠️ Troubleshooting

### Common Issues

#### "Not logged in" Error
- **Cause**: Session expired or cookies disabled
- **Solution**: Clear browser cache and login again

#### "No API token configured"
- **Cause**: API token not saved or invalid
- **Solution**: Re-enter your API token in the panel

#### "Network error while fetching zones"
- **Cause**: Invalid API token or network issues
- **Solution**: Check token permissions and internet connection

#### Cron job not working
- **Check cron logs**: `grep CRON /var/log/syslog`
- **Test manually**: `php auto_update.php`
- **Check PHP path**: `which php`

### Debug Mode
Run the auto-update script manually to see detailed output:
```bash
php auto_update.php
```

## 🔒 Security Considerations

### Best Practices
- **Change default password** immediately
- **Use strong passwords** (8+ characters, mixed case, numbers, symbols)
- **Restrict file permissions**: `chmod 600 config.json`
- **Use HTTPS** for the web interface
- **Regular token rotation** (every 6-12 months)

### File Permissions
```bash
chmod 755 *.php           # PHP files
chmod 600 config.json     # Configuration (sensitive)
chmod 644 *.json          # Other JSON files
chmod 644 *.txt           # Text files
```

## 📊 Monitoring

### Update Logs
- **Access**: Click "View Update Logs" in the panel
- **Information**: Timestamp, domain, IP, status, type (auto/manual)
- **Statistics**: Success rate, error count, update frequency

### Log Rotation
- **Automatic**: Keeps last 100 entries
- **Manual cleanup**: Delete `update_log.json` to reset

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Cloudflare** for their excellent API
- **PHP community** for security best practices
- **Contributors** who help improve this project

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/IloveKOHL/cloudflare-dyndns-panel/issues)
- **Documentation**: This README and inline comments
- **Community**: [Discussions](https://github.com/IloveKOHL/cloudflare-dyndns-panel/discussions)

---

**Made with ❤️ for the self-hosting community**

*Keep your dynamic DNS records updated automatically and securely!*
```

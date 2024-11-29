# Integration between APSIS One and Adobe Commerce / Magento
 
 
[![License: MPL 2.0](https://img.shields.io/badge/License-MPL%202.0-brightgreen.svg)](LICENSE)

This integration is provided as-is, free of charge, and without any warranty. It is open-source so the community can further enhance and maintain it.

## 📢 About This Integration
This repository contains the code and resources to integrate APSIS One with Adobe Commerce / Magento. APSIS is no longer actively maintaining it, but does encourage the community to contribute and improve it.

## 🚀 Getting Started
Looking for guidance on how to use this integration?<br />
👉 [APSIS One knowledge base for Magento](https://help.apsis.one/en/collections/3918264-magento)


## 🧪 Want to try APSIS One for Free?
Interested in exploring what APSIS One can do?  
👉 [Click here to set up a free trial account](https://apsis.com/freetrial)


## 💡 Contributing
We welcome community contributions! Feel free to submit issues, pull requests, or suggestions to help improve this integration.

## 🤝 Support
This integration is provided as-is, with no support or guarantees. For any issues, please use the GitHub Issues section.

---

Thank you for your interest in APSIS One and Adobe Commerce / Magento!  

Happy integrating! 🚀

# Technical documentation

## Requirements

- Magento 2.4.6 and above are compatible from version 2.1.1
  - PHP 8.1+
- Magento 2.4.4 & 2.4.5 is compatible with version 2.1.0
  - PHP 8.1+
- Magento 2.2.x, 2.3.x & 2.4.0 to 2.4.3-p3 is compatible up to version 2.0.8
  - PHP 7.1+
- Magento module(s) dependency 
    - Newsletter
    - Review
    - Sales
    - Wishlist
    - Store
    - Config
    - Backend
    - Cron
    - Customer
    - Catalog
    - Quote
    - Checkout
    - Ui
- APSIS One [Account](https://www.apsis.com/about-us/request-tour)

## Installation

It is recommended to use [composer](https://getcomposer.org) to install the module.

```bash
# Install latest version for Magento 2.4.4 and above
$ composer require apsis/module-one

# Install latest version for Magento 2.2.x, 2.3.x & 2.4.0 to 2.4.3-p3
$ composer require apsis/module-one:~2.0

# Update to latest version for Magento 2.4.4 and above
$ composer update apsis/module-one

# Update to latest version for Magento 2.2.x, 2.3.x, 2.4.0  to 2.4.3-p3
$ composer update apsis/module-one:~2.0
```
If you do not use composer, ensure that you also load any dependencies that this module has.

## Support

Full support documentation and setup guides available [here](https://help.apsis.one/en/).

## Contribution

You are welcome to contribute to our APSIS One integration module for Magento 2. You can either:
- Report a bug: Create a [GitHub issue](https://github.com/ApsisInternational/module-one/issues/new) including detailed description, steps to reproduce issue, Magento version including edition and module version number.
- To request a feature: Please contact us through [support](https://www.apsis.com/services/support)

## Internal Docs
[Documentation](https://efficy-my.sharepoint.com/:w:/p/aqa/EYmCpcUADPxAnH9SLlkiwRMBOJC_VtqpLQ5lbcSTNnInnQ?e=5bSRe1)

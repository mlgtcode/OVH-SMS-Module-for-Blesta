# OVH SMS Messenger Module for Blesta

The OVH SMS Messenger module allows you to send SMS notifications using the OVH API. This module integrates seamlessly with Blesta, enabling you to configure and manage SMS notifications for your clients.

## Features

- Send SMS notifications via the OVH SMS API.
- Easy configuration through the Blesta interface.

## Requirements

- Blesta 5.0 or later.
- An active OVH SMS account.

## Installation

1. **Download the Module**  
   Clone or download this repository to your local machine.

2. **Upload the Module**  
   Upload the `ovh` folder to your Blesta installation under the following path:  
   ```
   /components/messengers/
   ```

3. **Activate the Module**  
   - Log in to your Blesta admin panel.
   - Navigate to **Settings > Company > Modules > Installed**.
   - Click on **Add Messenger** and select **OVH** from the list.
   - Click **Install**.

4. **Configure the Module**  
   - Go to **Settings > Company > Modules > Installed**.
   - Click on **Manage** next to the OVH Messenger module.
   - Fill in the required fields:
     - **From**: The sender name or number. **Note**: The sender must be whitelisted via the OVH dashboard.
     - **Login**: Your OVH SMS API user login. **Note**: Create an SMS API user via the OVH console.
     - **Password**: The password for your OVH SMS API user.
     - **Account**: Your OVH account identifier.
   - Save the configuration.

## Useful Links

- [OVH Documentation](https://help.ovhcloud.com/csm/en-gb-sms-sending-via-url-http2sms?id=kb_article_view&sysparm_article=KB0039184)
- [OVHCloud Manager](https://www.ovhtelecom.fr/manager/#/telecom/sms)
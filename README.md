# Transactpay
the official woocommerce plugin for Transactpay.

## Development Dependencies

1. wp-env
2. Github Codespaces
3. Gitpod


## Buidling a complete package.
This plugin makes use of `jetpack-autoloader`. make sure you run the command below to generate the required files.
```shell 
composer dump-autoload -o
```

To Create a zip file to share with merchants. Simply run the command below.

```shell
pnpm build
```

# How to Install a WordPress Plugin

Installing a WordPress plugin is a simple process. Follow the steps below to add and activate plugins on your WordPress site.

## Method 1: Installing a Plugin from the WordPress Dashboard

1. **Login to WordPress Admin**
   - Go to `yourwebsite.com/wp-admin` and log in with your credentials.

2. **Navigate to Plugins Section**
   - In the WordPress dashboard, on the left-hand menu, click on `Plugins` and select `Add New`.

3. **Search for a Plugin**
   - Use the search bar on the top-right to find the plugin you want to install. For example, search for "Transactpay".

4. **Install the Plugin**
   - Once you've found your desired plugin, click the `Install Now` button.

5. **Activate the Plugin**
   - After installation, click the `Activate` button to start using the plugin.

---

## Method 2: Installing a Plugin by Uploading a Zip File

1. **Download the Plugin Zip File**
   - Obtain the `.zip` file for the plugin from the official WordPress plugin directory or from Github.

2. **Login to WordPress Admin**
   - Go to `yourwebsite.com/wp-admin` and log in with your credentials.

3. **Navigate to Plugins Section**
   - In the WordPress dashboard, click on `Plugins` > `Add New`.

4. **Upload the Plugin**
   - At the top of the page, click `Upload Plugin`. Then click the `Choose File` button and select the `.zip` file you downloaded.

5. **Install and Activate the Plugin**
   - Once the file is uploaded, click `Install Now`, and after installation, click `Activate` to start using the plugin.

---

## Method 3: Installing a Plugin via FTP

1. **Download the Plugin Zip File**
   - Obtain the `.zip` file for the plugin and extract it to a folder on your computer.

2. **Connect to Your Website via FTP**
   - Use an FTP client like FileZilla to connect to your website server. You will need your FTP login credentials.

3. **Upload the Plugin**
   - Navigate to the `/wp-content/plugins/` directory on your server. Upload the extracted plugin folder to this directory.

4. **Activate the Plugin**
   - After uploading, go to the WordPress dashboard. Navigate to `Plugins`, find your newly uploaded plugin, and click `Activate`.

---

## Troubleshooting

- If you receive an error like `The plugin does not have a valid header`, the plugin may not be properly installed. Try deleting and reinstalling it.
- Make sure your plugin is compatible with your version of WordPress by checking the plugin documentation.


# USAePay Payments
## Description
description of extension goes here
## Installation / Configuration
#### Prepare your USAePay account
1. Login to your USAePay account
  - [USAePay Login](https://www.usaepay.com/login)
  - [Sandbox Login](https://sandbox.usaepay.com/login)
- Navigate to **Settings** => **Source Keys**
- Click **Add Source**
- Enter a Name for your **Source Key**
  - If you plan on using recurring contributions in CiviCRM, you are required to also define a **PIN** here
- Click **Apply**. This will generate your **Source Key** and save your settings

#### Install USAePay Payments extension
1. Download the [latest release](https://github.com/proexchange/com.pesc.usaepay/archive/0.9.tar.gz) of the extension from GitHub
- Extract the tarball (`tar -xzf 0.9.tar.gz`) to your CiviCRM Extensions directory. This can be found by navigating to **Administer** => **System Settings** => **Directories**
  - After extracting, make sure the name of the directory is **com.pesc.usaepay**; if this is not the case, rename the directory
- Login to your CiviCRM website and navigate to **Administer** => **System Settings** => **Extensions**
- Click **Refresh**, then install and enable the extension

#### Setup a USAePay payment processor
1. Navigate to **Administer** => **System Settings** => **Payment Processors**
- Click **Add Payment Processor**
- Create a new payment processor and select the type **USAePay Payments Credit Card** or **USAePay Payments ACH**
- Select a **Financial Account**, and your **Accepted Credit Card Type(s)**. Enter your USAePay **Source Key** and **PIN** (optional) into the Processor Details, as well as the **Source Key** and **PIN** for your sandbox account (if you have one)
- When setting up Event pages, Contribution pages, etc in CiviCRM, you will now be able to select this payment processor so the payments get processed through USAePay

## Setting up recurring contributions (optional)
1. Navigate to **Administer** => **System Settings** => **Scheduled Jobs**
- Edit the **USAePay Fetch Transactions** job, enter your **Source Key** and **PIN**, and make the job active
  - A **PIN** is required for recurring contributions, so if you didn't set one up in your USAePay account dashboard, go to that now
  - Using a **Source Name** is optional. This filters which recurring contributions get pulled into CiviCRM from USAePay. This useful when USAePay is used with more than just this instance of CiviCRM
  - Example:
  ```
  sourcekey=11111111111111111111111111111111
  pin=1111
  sourcename=civicrm
  ```

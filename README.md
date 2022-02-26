# File Manager With Google Drive
The file manager uses service account to access the google drive and perform CRUD operation.

# How to get credentials for service account

1. Visit Google Cloud Console: 
https://console.cloud.google.com

2. Create New Project: 
https://console.cloud.google.com/projectcreate

![image](https://user-images.githubusercontent.com/49345140/155847262-492179bb-e5f2-4ffd-98f4-42d2f3a07049.png)

3. Select Project
![image](https://user-images.githubusercontent.com/49345140/155847306-6e1640a3-3114-4d4c-9866-910341585aab.png)

4. Go to APIs overview
![image](https://user-images.githubusercontent.com/49345140/155847567-6e89088d-5efb-4ccc-b6ef-9e3372039952.png)

5. Enable APIs and Services
![image](https://user-images.githubusercontent.com/49345140/155847637-6cc53ffa-83c8-46a1-9121-7b8977870348.png)

6. Search for "Google Drive API"
![image](https://user-images.githubusercontent.com/49345140/155847712-134ce036-0ff8-4f95-a7cc-c49bae78f27e.png)

7. Enable API
![image](https://user-images.githubusercontent.com/49345140/155847802-5e9a0276-cdc7-4848-987d-e816016871f2.png)

8. Create Credentials
![image](https://user-images.githubusercontent.com/49345140/155847911-3b3f598b-4f17-4b7c-ba34-c4fa7d6d8d24.png)

9. Select the following options to create service account, then click NEXT button
![image](https://user-images.githubusercontent.com/49345140/155847980-71c38e14-4ac8-4b57-ae67-b7f776f29c29.png)

10. Enter the following details, then click CREATE AND CONTINUE button
![image](https://user-images.githubusercontent.com/49345140/155848054-8859bb83-cd31-40f4-baae-40da55aa3040.png)

11. Choose Owner from the Role dropdown, then click CONTINUE button
![image](https://user-images.githubusercontent.com/49345140/155848122-8822d919-78d1-49fe-b39b-3ace971bd28b.png)

12. Leave the following inputs blank, then click DONE button
![image](https://user-images.githubusercontent.com/49345140/155848174-06bf4297-6584-4e75-84c4-19c973bdbda2.png)

13. Click on CREDENTIALS tab, then click on the newly created service account email
![image](https://user-images.githubusercontent.com/49345140/155848304-1e2e853c-c6c7-46fd-acf2-8f0002352014.png)

14. Click on KEYS tab
![image](https://user-images.githubusercontent.com/49345140/155848714-86be2182-3b18-4b06-a6bc-02bf2248752a.png)

15. Click on ADD KEY button, then click on Create new key from dropdown
![image](https://user-images.githubusercontent.com/49345140/155848783-45987d76-8554-41c3-a45e-8f31b244939e.png)

16. Select JSON from the option, then click CREATE button and save the JSON file in your project folder, rename it to "credentials.json"
![image](https://user-images.githubusercontent.com/49345140/155848817-cca1e507-6a79-4432-8ad2-589dd60164dc.png)

17. Copy the email address from Service account details page
![image](https://user-images.githubusercontent.com/49345140/155848997-45e24666-9bcc-4ab6-a95c-78e0f16c67c6.png)

18. Go to Your Google Drive: 
https://drive.google.com/drive/my-drive

19. Create a New Folder, and share that folder to the service account email
![image](https://user-images.githubusercontent.com/49345140/155849290-22df8bd8-792e-4be4-83f9-faa303265360.png)

20. Copy the Folder ID from the URL
![image](https://user-images.githubusercontent.com/49345140/155849387-b9da09b0-1ce7-43bb-ba4d-30b2b2a04502.png)

21. Replace the "$targetDirectory" variable's value with the new folder ID
![image](https://user-images.githubusercontent.com/49345140/155849439-b43e330f-5338-42c1-afb5-cee45b4037d7.png)

22. Now you are all set. Open the project in your localhost and try to upload files to your drive.
![image](https://user-images.githubusercontent.com/49345140/155849546-697ce5ed-ecb3-4268-a1a5-33209566d497.png)

23. Check your drive folder to verify that the files have uploaded successfully
![image](https://user-images.githubusercontent.com/49345140/155849623-edabfb82-729a-4340-b344-0f08b1b78a80.png)

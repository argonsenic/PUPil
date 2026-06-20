# Render.com Deployment Guide

This guide will help you deploy your PUP Attendance System to Render.com using PostgreSQL.

## Prerequisites

- ✅ GitHub repository with your code
- ✅ Render.com account (free)
- ✅ PostgreSQL 15 database (free on Render)

## Step 1: Create PostgreSQL Database on Render

1. **Go to Render.com** → Dashboard
2. **Click "New +"** → "PostgreSQL"
3. **Configure database**:
   - **Name**: `pup-attendance-db`
   - **Database**: `attendance_system`
   - **User**: `attendance_user`
   - **Region**: Singapore (closest to Philippines)
   - **Plan**: Free (should be selected by default)
4. **Click "Create Database"**

## Step 2: Get Database Credentials

1. **Go to your PostgreSQL database** on Render
2. **Scroll down to "Connections"**
3. **Copy these credentials**:
   - **Host**: `xxx.xxx.compute-1.amazonaws.com`
   - **Port**: `5432`
   - **Database**: `attendance_system`
   - **User**: `attendance_user`
   - **Password**: (click "Show" to copy)

## Step 3: Run Database Schema

1. **Go to your PostgreSQL database** on Render
2. **Click "Connect"** → "External Connection"
3. **Use pgAdmin** or **psql** to connect
4. **Run the schema file**:
   ```bash
   psql -h YOUR_HOST -p 5432 -U attendance_user -d attendance_system -f schema_postgresql.sql
   ```

## Step 4: Create Web Service

1. **Go to Render.com** → Dashboard
2. **Click "New +"** → "Web Service"
3. **Connect your GitHub repository**
4. **Configure settings**:
   - **Name**: `pup-attendance-system`
   - **Region**: Singapore
   - **Branch**: `main`
   - **Runtime**: PHP
   - **Root Directory**: `IM_Database-Fronted-main/IM_Database`
   - **Build Command**: (leave empty for auto-detect)
   - **Start Command**: `php -S 0.0.0.0:8000`

## Step 5: Add Environment Variables

1. **Scroll down to "Environment Variables"**
2. **Add these variables**:
   ```
   DB_HOST = your-database-host.compute-1.amazonaws.com
   DB_PORT = 5432
   DB_NAME = attendance_system
   DB_USER = attendance_user
   DB_PASSWORD = your-database-password
   ```

## Step 6: Deploy

1. **Click "Create Web Service"**
2. **Wait for deployment** (takes 2-5 minutes)
3. **Your site will be live at**: `https://pup-attendance-system.onrender.com`

## Step 7: Create Admin User

1. **Access your deployed site**: `https://pup-attendance-system.onrender.com/create_admin.php`
2. **This will create** the admin user (pupil/pupil)

## Step 8: Test Your Application

1. **Go to**: `https://pup-attendance-system.onrender.com/login.html`
2. **Login with**: Username `pupil`, Password `pupil`
3. **Test registration**: Create a new student account
4. **Test student login**: Login with the new student account

## Important Notes

- **Free tier limitations**: 
  - Database spins down after inactivity (may take 30 seconds to wake up)
  - Web service spins down after inactivity (may take 30 seconds to wake up)
  - Limited to 100MB database size
  - Limited to 512MB RAM

- **Custom domain**: 
  - You can add a custom domain in Render settings
  - Requires DNS configuration

- **Security**:
  - Your database credentials are in environment variables (secure)
  - HTTPS is automatically enabled
  - Consider adding rate limiting for production

## Troubleshooting

### Database Connection Error
- Check environment variables are correct
- Verify database is running (not spun down)
- Check firewall settings

### 503 Service Unavailable
- Service may be spinning up (wait 30 seconds)
- Check deployment logs in Render dashboard
- Verify start command is correct

### Registration Errors
- Ensure database schema was run successfully
- Check database logs in Render dashboard
- Verify admin user was created

## Next Steps

1. **Share the URL** with your students
2. **Monitor usage** in Render dashboard
3. **Consider upgrading** if you hit free tier limits
4. **Set up backups** (Render has automatic backups)

## Cost Summary

- **Web Service**: Free
- **PostgreSQL Database**: Free
- **Total**: $0/month (with limitations)

## Support

If you encounter issues:
- Check Render deployment logs
- Review database logs
- Test database connection locally first
- Check Render status page: https://status.render.com/

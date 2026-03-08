package com.example.libraryattendance.data.local

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase

@Database(entities = [User::class, AttendanceLog::class], version = 12, exportSchema = false)
abstract class LibraryDatabase : RoomDatabase() {
    abstract fun userDao(): UserDao
    abstract fun attendanceLogDao(): AttendanceLogDao

    companion object {
        @Volatile
        private var INSTANCE: LibraryDatabase? = null

        fun getDatabase(context: Context): LibraryDatabase {
            return INSTANCE ?: synchronized(this) {
                val instance = Room.databaseBuilder(
                    context.applicationContext,
                    LibraryDatabase::class.java,
                    "library_attendance_db"
                )
                .fallbackToDestructiveMigration() // Allow destructive migration for dev updates
                .build()
                INSTANCE = instance
                instance
            }
        }
    }
}

package com.example.libraryattendance

import android.app.Application
import com.example.libraryattendance.data.local.LibraryDatabase
import com.example.libraryattendance.data.repository.LibraryRepository

class LibraryApplication : Application() {
    val database by lazy { LibraryDatabase.getDatabase(this) }
    val repository by lazy { LibraryRepository(database.userDao(), database.attendanceLogDao(), this) }
}

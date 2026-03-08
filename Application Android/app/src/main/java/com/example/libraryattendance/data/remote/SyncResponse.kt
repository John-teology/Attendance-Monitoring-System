package com.example.libraryattendance.data.remote

data class SyncResponse(
    val success: Boolean,
    val message: String,
    val syncedIds: List<Long>,
    val entry_type: String? = null,
    val user: UserDto? = null
)

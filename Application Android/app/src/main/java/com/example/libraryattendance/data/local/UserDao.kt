package com.example.libraryattendance.data.local

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query

@Dao
interface UserDao {
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertUser(user: User): Long

    @Query("SELECT * FROM users WHERE qr_code = :qrCode LIMIT 1")
    suspend fun getUserByQr(qrCode: String): User?

    @Query("SELECT * FROM users WHERE rfid_uid = :rfidUid LIMIT 1")
    suspend fun getUserByRfid(rfidUid: String): User?
    
    @Query("SELECT * FROM users WHERE id_number = :idNumber LIMIT 1")
    suspend fun getUserByIdNumber(idNumber: String): User?

    @Query("SELECT * FROM users WHERE id = :id LIMIT 1")
    suspend fun getUserById(id: Long): User?
    
    @Query("SELECT * FROM users")
    suspend fun getAllUsers(): List<User>
}

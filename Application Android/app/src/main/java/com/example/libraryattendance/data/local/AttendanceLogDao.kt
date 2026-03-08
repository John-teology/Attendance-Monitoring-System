package com.example.libraryattendance.data.local

import androidx.room.ColumnInfo
import androidx.room.Dao
import androidx.room.Insert
import androidx.room.Query
import kotlinx.coroutines.flow.Flow

@Dao
interface AttendanceLogDao {
    @Insert
    suspend fun insertLog(log: AttendanceLog)

    @Query("SELECT * FROM attendance_logs ORDER BY timestamp DESC")
    fun getAllLogs(): Flow<List<AttendanceLog>>
    
    @Query("SELECT * FROM attendance_logs WHERE sync_status = 0")
    suspend fun getUnsyncedLogs(): List<AttendanceLog>

    @Query("SELECT l.id, l.timestamp, l.scan_type, u.id_number FROM attendance_logs l INNER JOIN users u ON l.user_id = u.id WHERE l.sync_status = 0")
    suspend fun getUnsyncedLogsForSync(): List<SyncLogDto>

    @Query("SELECT COUNT(DISTINCT user_id) FROM attendance_logs WHERE timestamp >= :startOfDay")
    fun getTodayCount(startOfDay: Long): Flow<Int>

    @Query("SELECT COUNT(*) FROM attendance_logs WHERE sync_status = 0")
    fun getPendingSyncCount(): Flow<Int>

    @Query("SELECT COUNT(*) FROM attendance_logs WHERE sync_status = 1")
    fun getSyncedCount(): Flow<Int>

    // Ongoing Count: Users whose LAST log for today is 'IN'
    // We filter by today's logs, group by user, and check if the latest log is IN
    @Query("""
        SELECT COUNT(*) FROM (
            SELECT user_id FROM attendance_logs 
            WHERE timestamp >= :startOfDay 
            GROUP BY user_id 
            HAVING (
                SELECT entry_type FROM attendance_logs AS l2 
                WHERE l2.user_id = attendance_logs.user_id 
                AND l2.timestamp >= :startOfDay
                ORDER BY l2.timestamp DESC 
                LIMIT 1
            ) = 'IN'
        )
    """)
    fun getOngoingCount(startOfDay: Long): Flow<Int>
}

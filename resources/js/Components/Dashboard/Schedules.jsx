import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig';
import { collection, query, where, onSnapshot, orderBy, doc, deleteDoc, updateDoc } from 'firebase/firestore';
import axios from 'axios';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrash, faEdit, faCalendar, faTimes, faComment, faUnlock } from '@fortawesome/free-solid-svg-icons';
import ChatScreen from './ChatScreen';
import CoachingPlanModal from './CoachingPlanModal';

export default function Schedules({ currentUserId }) {
    // Get today's date at midnight local time
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const [selectedDate, setSelectedDate] = useState(today);
    const [selectedFilter, setSelectedFilter] = useState('ALL TASKS');
    const [schedules, setSchedules] = useState([]);
    const [coachingSessions, setCoachingSessions] = useState([]);
    const [loading, setLoading] = useState(true);
    
    // Track data loading status
    const [dataStatus, setDataStatus] = useState({
        discoveryCallsLoaded: false,
        coachingSessionsLoaded: false
    });
    
    // Create proper date range with proper Date objects
    const oneMonthLater = new Date(today);
    oneMonthLater.setMonth(today.getMonth() + 1);
    
    const [dateRange, setDateRange] = useState({
        startDate: today,
        endDate: oneMonthLater
    });
    
    const [isDateRangeActive, setIsDateRangeActive] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [editingSchedule, setEditingSchedule] = useState(null);
    const [showChat, setShowChat] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState(null);
    const [showCoachingPlanModal, setShowCoachingPlanModal] = useState(false);
    const [selectedSchedule, setSelectedSchedule] = useState(null);

    useEffect(() => {
        console.log('[YOLO] Component mounted with currentUserId:', currentUserId);
        console.log('[YOLO] Initial loading state:', loading);
        
        if (!currentUserId) {
            console.log('[YOLO] No currentUserId provided, skipping fetch');
            setLoading(false);
            return;
        }

        const fetchSchedules = async () => {
            console.log('[YOLO] Starting fetchSchedules function');
            try {
                const discoveryCallsCollection = collection(db, "discoveryCallRequest");
                const schedulesQuery = query(
                    discoveryCallsCollection,
                    where("coachId", "==", Number(currentUserId)),
                    where("status", "in", ["accepted", "code created & shared"]),
                    orderBy("createdAt", "desc")
                );

                console.log('[YOLO] Discovery calls query parameters:', {
                    coachId: Number(currentUserId),
                    statusIn: ["accepted", "code created & shared"]
                });

                // Also fetch coaching sessions
                const coachingSessionsCollection = collection(db, "coachingSessions");
                const sessionsQuery = query(
                    coachingSessionsCollection,
                    where("coachId", "==", Number(currentUserId)),
                    orderBy("createdAt", "desc")
                );

                console.log('[YOLO] Coaching sessions query parameters:', {
                    coachId: Number(currentUserId)
                });

                // Listen for discovery calls
                const unsubscribeDiscovery = onSnapshot(schedulesQuery, async (snapshot) => {
                    console.log('[YOLO] Discovery calls snapshot received:', {
                        size: snapshot.size,
                        empty: snapshot.empty
                    });
                    
                    snapshot.docs.forEach((doc, index) => {
                        console.log(`[YOLO] Discovery call document ${index}:`, {
                            id: doc.id,
                            data: doc.data()
                        });
                    });

                    const schedulesList = [];
                    
                    for (const doc of snapshot.docs) {
                        const data = doc.data();
                        console.log('[YOLO] Processing discovery call document:', {
                            id: doc.id,
                            data: data
                        });
                        try {
                            // Fetch user name for each request
                            console.log('[YOLO] Fetching user data for userId:', data.userId);
                            const response = await axios.get(`/api/users/${data.userId}`);
                            console.log('[YOLO] User API response:', response.data);
                            const userName = response.data.name;

                            // Convert preferred_time to AM/PM format
                            const timeStr = data.preferred_time;
                            const [hours, minutes] = timeStr.split(':');
                            const date = new Date();
                            date.setHours(parseInt(hours), parseInt(minutes));
                            const formattedTime = date.toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: 'numeric',
                                hour12: true
                            });

                            // Format preferred_date
                            const preferredDate = new Date(data.preferred_date);
                            const formattedDate = preferredDate.toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric'
                            }).toUpperCase();

                            const scheduleItem = {
                                id: doc.id,
                                name: userName,
                                time: formattedTime,
                                date: formattedDate,
                                type: 'discovery call',
                                topic: data.topic,
                                rawDate: data.preferred_date,
                                rawTime: data.preferred_time,
                                status: data.status,
                                userId: data.userId,
                                coachId: data.coachId,
                                meetingLink: data.meetingLink || ''
                            };
                            
                            console.log('[YOLO] Created schedule item:', scheduleItem);
                            schedulesList.push(scheduleItem);
                        } catch (error) {
                            console.error("[YOLO] Error fetching user details:", error);
                        }
                    }

                    console.log('[YOLO] Setting schedules state with:', schedulesList);
                    setSchedules(schedulesList);
                    
                    // Mark discovery calls as loaded
                    setDataStatus(prev => ({
                        ...prev,
                        discoveryCallsLoaded: true
                    }));
                    
                    console.log('[YOLO] After setting schedules - updated dataStatus:', {
                        ...dataStatus,
                        discoveryCallsLoaded: true
                    });
                });

                // Listen for coaching sessions
                const unsubscribeSessions = onSnapshot(sessionsQuery, async (snapshot) => {
                    console.log('[YOLO] Coaching sessions snapshot received:', {
                        size: snapshot.size,
                        empty: snapshot.empty
                    });
                    
                    snapshot.docs.forEach((doc, index) => {
                        console.log(`[YOLO] Coaching session document ${index}:`, {
                            id: doc.id,
                            data: doc.data()
                        });
                    });
                    
                    const sessionsList = [];
                    
                    for (const doc of snapshot.docs) {
                        const data = doc.data();
                        console.log('[YOLO] Processing coaching session document:', {
                            id: doc.id,
                            data: data
                        });
                        try {
                            // Fetch user name for each session
                            console.log('[YOLO] Fetching user data for userId:', data.userId);
                            const response = await axios.get(`/api/users/${data.userId}`);
                            console.log('[YOLO] User API response for coaching session:', response.data);
                            const userName = response.data.name;

                            // Convert time to AM/PM format
                            const timeStr = data.time;
                            const [hours, minutes] = timeStr.split(':');
                            const date = new Date();
                            date.setHours(parseInt(hours), parseInt(minutes));
                            const formattedTime = date.toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: 'numeric',
                                hour12: true
                            });

                            // Format date
                            const sessionDate = new Date(data.date);
                            const formattedDate = sessionDate.toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric'
                            }).toUpperCase();

                            const sessionItem = {
                                id: doc.id,
                                name: userName,
                                time: formattedTime,
                                date: formattedDate,
                                type: 'coaching session',
                                topic: `${data.planTitle} - ${data.sessionLabel}`,
                                rawDate: data.date,
                                rawTime: data.time,
                                status: data.status,
                                userId: data.userId,
                                coachId: data.coachId,
                                meetingLink: data.meetingLink,
                                sessionNumber: data.sessionNumber,
                                planCode: data.planCode
                            };
                            
                            console.log('[YOLO] Created coaching session item:', sessionItem);
                            sessionsList.push(sessionItem);
                        } catch (error) {
                            console.error("[YOLO] Error fetching user details for coaching session:", error);
                        }
                    }

                    console.log('[YOLO] Setting coachingSessions state with:', sessionsList);
                    setCoachingSessions(sessionsList);
                    
                    // Mark coaching sessions as loaded
                    setDataStatus(prev => ({
                        ...prev,
                        coachingSessionsLoaded: true
                    }));
                    
                    console.log('[YOLO] After setting coachingSessions - updated dataStatus:', {
                        ...dataStatus,
                        coachingSessionsLoaded: true
                    });
                });

                return () => {
                    console.log('[YOLO] Cleaning up subscription');
                    unsubscribeDiscovery();
                    unsubscribeSessions();
                };
            } catch (error) {
                console.error("[YOLO] Error in fetchSchedules:", error);
                setLoading(false);
            }
        };

        fetchSchedules();
        
        // Safety timeout to prevent infinite loading if something goes wrong
        const safetyTimer = setTimeout(() => {
            console.log('[YOLO] Safety timeout triggered - forcing loading to false');
            setLoading(false);
        }, 10000); // 10 seconds timeout
        
        return () => {
            clearTimeout(safetyTimer);
        };
    }, [currentUserId]);
    
    // Use effect to track when both data sources are loaded
    useEffect(() => {
        console.log('[YOLO] dataStatus changed:', dataStatus);
        if (dataStatus.discoveryCallsLoaded && dataStatus.coachingSessionsLoaded) {
            console.log('[YOLO] Both data sources loaded, setting loading to false');
            setLoading(false);
        }
    }, [dataStatus]);

    // Generate array of dates for the calendar
    const generateCalendarDates = () => {
        const dates = [];
        const today = new Date();
        for (let i = -3; i <= 4; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            dates.push(date);
        }
        return dates;
    };

    const getTaskTypeStyle = (type) => {
        switch (type) {
            case 'discovery call':
                return 'bg-[#85C240] text-white';
            case 'coaching session':
                return 'bg-[#85C240] text-white';
            case 'check in':
                return 'bg-[#FF7A50] text-white';
            case 'action plans':
                return 'bg-[#50B5FF] text-white';
            default:
                return 'bg-gray-500 text-white';
        }
    };

    const handleDelete = async (scheduleId) => {
        const confirmDelete = window.confirm("Are you sure you want to delete this schedule?");
        if (confirmDelete) {
            try {
                await deleteDoc(doc(db, "discoveryCallRequest", scheduleId));
                alert("Schedule deleted successfully.");
            } catch (error) {
                console.error("Error deleting schedule:", error);
            }
        }
    };

    // Combine discovery calls and coaching sessions
    const allSchedules = [...schedules, ...coachingSessions];
    
    console.log('[YOLO] Combined schedules for display:', {
        discoveryCallsCount: schedules.length,
        coachingSessionsCount: coachingSessions.length,
        totalCount: allSchedules.length
    });
    
    // Add debug output for coaching sessions
    if (coachingSessions.length > 0) {
        console.log('[YOLO] Coaching sessions available for display:', 
            coachingSessions.map(session => ({
                id: session.id,
                date: session.date,
                rawDate: session.rawDate,
                planTitle: session.topic,
                planCode: session.planCode
            }))
        );
    }
    
    // Modified filter function to handle both single date and date range
    const filteredSchedules = allSchedules.filter(schedule => {
        // Parse the raw date string from the schedule (YYYY-MM-DD format)
        // First, make sure we have a valid date string
        if (!schedule.rawDate) {
            console.log('[YOLO] Schedule missing rawDate:', schedule);
            return false;
        }
        
        // Create a date object in local timezone from the YYYY-MM-DD string
        const dateString = schedule.rawDate.split('T')[0]; // Handle both "2025-03-05" and "2025-03-05T00:00:00" formats
        const [year, month, day] = dateString.split('-').map(num => parseInt(num, 10));
        
        // Create a new Date at midnight local time for the schedule date
        const scheduleDate = new Date(year, month - 1, day); // month is 0-indexed in JS
        
        // Special case for newly created coaching sessions and COACHING SESSION filter
        if (selectedFilter === 'COACHING SESSION' && schedule.type === 'coaching session') {
            // For coaching sessions filter, show all coaching sessions regardless of date
            // if the user has specifically selected this filter
            return true;
        }
        
        if (isDateRangeActive) {
            // Create comparable date objects for start and end dates
            let startDate, endDate;
            
            if (dateRange.startDate instanceof Date) {
                // Get year, month, day components from the Date object
                const startYear = dateRange.startDate.getFullYear();
                const startMonth = dateRange.startDate.getMonth(); // already 0-indexed
                const startDay = dateRange.startDate.getDate();
                startDate = new Date(startYear, startMonth, startDay);
            } else {
                // If somehow not a Date, use today
                startDate = new Date();
                startDate.setHours(0, 0, 0, 0);
            }
            
            if (dateRange.endDate instanceof Date) {
                // Get year, month, day components from the Date object
                const endYear = dateRange.endDate.getFullYear();
                const endMonth = dateRange.endDate.getMonth(); // already 0-indexed
                const endDay = dateRange.endDate.getDate();
                endDate = new Date(endYear, endMonth, endDay);
                // Set to end of day
                endDate.setHours(23, 59, 59, 999);
            } else {
                // If somehow not a Date, use today
                endDate = new Date();
                endDate.setHours(23, 59, 59, 999);
            }
            
            // Compare the schedule date against the date range
            return scheduleDate >= startDate && scheduleDate <= endDate;
        } else {
            // Compare to selected date
            const selectedDateMidnight = new Date(selectedDate);
            selectedDateMidnight.setHours(0, 0, 0, 0);
            
            console.log('[YOLO] Comparing dates for filtering:', {
                scheduleDate: scheduleDate.toISOString(),
                selectedDate: selectedDateMidnight.toISOString(),
                isSameDay: scheduleDate.getTime() === selectedDateMidnight.getTime()
            });
            
            // Compare year, month and day components
            return scheduleDate.getFullYear() === selectedDateMidnight.getFullYear() && 
                   scheduleDate.getMonth() === selectedDateMidnight.getMonth() && 
                   scheduleDate.getDate() === selectedDateMidnight.getDate();
        }
    });

    console.log('[YOLO] Filtered schedules after date filtering:', {
        allSchedulesCount: allSchedules.length,
        filteredSchedulesCount: filteredSchedules.length,
        isDateRangeActive: isDateRangeActive,
        selectedDate: selectedDate.toISOString(),
    });

    // Filter by type 
    const typeFilteredSchedules = filteredSchedules.filter(schedule => {
        if (selectedFilter === 'ALL TASKS') return true;
        return schedule.type.toUpperCase() === selectedFilter;
    });

    console.log('[YOLO] Type filtered schedules:', {
        filteredSchedulesCount: filteredSchedules.length,
        typeFilteredSchedulesCount: typeFilteredSchedules.length,
        selectedFilter: selectedFilter
    });

    const handleEdit = (schedule) => {
        setEditingSchedule(schedule);
        setIsEditing(true);
    };

    const handleEditClose = () => {
        setIsEditing(false);
        setEditingSchedule(null);
    };

    const handleEditSave = async (scheduleId, updatedData, scheduleType) => {
        try {
            if (scheduleType === 'coaching session') {
                const sessionRef = doc(db, "coachingSessions", scheduleId);
                await updateDoc(sessionRef, {
                    time: updatedData.preferred_time,
                    date: updatedData.preferred_date,
                    topic: updatedData.topic,
                    status: updatedData.status,
                    meetingLink: updatedData.meetingLink || ''
                });
            } else {
                const scheduleRef = doc(db, "discoveryCallRequest", scheduleId);
                await updateDoc(scheduleRef, updatedData);
            }
            setIsEditing(false);
            setEditingSchedule(null);
        } catch (error) {
            console.error("Error updating schedule:", error);
        }
    };

    const handleChat = () => {
        setSelectedUserId(filteredSchedules.find(schedule => schedule.status === 'pending')?.userId);
        setShowChat(true);
        handleEditClose();
    };

    const handleGenerateCode = (schedule) => {
        console.log('[YOLO] Generating code for schedule:', schedule);
        setSelectedSchedule(schedule);
        setShowCoachingPlanModal(true);
    };

    const handleSessionsCreated = (sessions) => {
        console.log('[YOLO] Sessions created callback received with sessions:', sessions);
        // Switch to COACHING SESSION filter to show the newly created sessions
        setSelectedFilter('COACHING SESSION');
        // If date range is active, ensure we're looking at the right dates
        if (sessions && sessions.length > 0 && sessions[0].date) {
            try {
                // Try to set selected date to the first session date
                const firstSessionDate = new Date(sessions[0].date);
                if (!isNaN(firstSessionDate.getTime())) {
                    console.log('[YOLO] Setting selected date to first session date:', firstSessionDate.toISOString());
                    setSelectedDate(firstSessionDate);
                }
            } catch (error) {
                console.error('[YOLO] Error setting selected date:', error);
            }
        }
    };

    if (loading) {
        return (
            <div className="flex-1 ml-64 mr-80 flex items-center justify-center">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#85C240]"></div>
            </div>
        );
    }

    if (showChat) {
        return (
            <ChatScreen 
                currentUserId={currentUserId}
                recipientId={selectedUserId}
                onClose={() => {
                    setShowChat(false);
                    setSelectedUserId(null);
                }}
            />
        );
    }

    // Edit Task Component
    const EditTask = ({ schedule, onClose, onSave }) => {
        const [taskData, setTaskData] = useState({
            topic: schedule.topic,
            date: schedule.rawDate,
            time: schedule.rawTime,
            status: schedule.status || 'pending',
            meetingLink: schedule.meetingLink || ''
        });

        const handleSave = () => {
            if (taskData.status === 'accepted' && !taskData.meetingLink) {
                alert('Please provide a meeting link for accepted discovery calls');
                return;
            }
            onSave(schedule.id, {
                topic: taskData.topic,
                preferred_date: taskData.date,
                preferred_time: taskData.time,
                status: taskData.status,
                meetingLink: taskData.meetingLink
            }, schedule.type);
        };

        const handleChat = () => {
            setSelectedUserId(schedule.userId);
            setShowChat(true);
            onClose();
        };

        return (
            <div className="bg-[#111315] rounded-xl p-6">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-2xl font-unbounded-700">EDIT {schedule.type.toUpperCase()}</h2>
                    <div className="flex items-center space-x-4">
                        <button 
                            onClick={handleChat}
                            className="flex items-center space-x-2 px-4 py-2 bg-[#FF7A50] text-white rounded-lg hover:bg-opacity-90 transition-all duration-300"
                        >
                            <FontAwesomeIcon icon={faComment} className="mr-2" />
                            <span className="font-unbounded-500">CHAT</span>
                        </button>
                        <button onClick={onClose} className="text-gray-500 hover:text-white transition-colors duration-300">
                            <FontAwesomeIcon icon={faTimes} />
                        </button>
                    </div>
                </div>

                <div className="mb-6">
                    <p className="text-lg font-unbounded-500">{schedule.name}</p>
                    <span className={`inline-block px-3 py-1 ${getTaskTypeStyle(schedule.type)} rounded-lg text-sm font-unbounded-500 mt-2`}>
                        {schedule.type}
                    </span>
                </div>

                <div className="space-y-6">
                    <div>
                        <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Task Title*</label>
                        <input
                            type="text"
                            value={taskData.topic}
                            onChange={(e) => setTaskData({ ...taskData, topic: e.target.value })}
                            className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                            placeholder="Enter Title"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Status</label>
                        <select
                            value={taskData.status}
                            onChange={(e) => setTaskData({ ...taskData, status: e.target.value })}
                            className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                        >
                            <option value="pending">PENDING</option>
                            <option value="accepted">ACCEPTED</option>
                            <option value="code created & shared">CODE CREATED & SHARED</option>
                            <option value="cancelled">CANCELLED</option>
                        </select>
                    </div>

                    {taskData.status === 'accepted' && (
                        <div>
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Meeting Link*</label>
                            <input
                                type="url"
                                value={taskData.meetingLink || ''}
                                onChange={(e) => setTaskData({ ...taskData, meetingLink: e.target.value })}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                placeholder="Enter Zoom/Google Meet link"
                                required
                            />
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Date</label>
                            <input
                                type="date"
                                value={taskData.date}
                                onChange={(e) => setTaskData({ ...taskData, date: e.target.value })}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">Time</label>
                            <input
                                type="time"
                                value={taskData.time}
                                onChange={(e) => setTaskData({ ...taskData, time: e.target.value })}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                            />
                        </div>
                    </div>

                    <button
                        onClick={handleSave}
                        className="w-full bg-[#85C240] text-white py-3 rounded-lg font-unbounded-600 hover:bg-opacity-90 transition-all duration-300"
                    >
                        SAVE CHANGES
                    </button>
                </div>
            </div>
        );
    };

    return (
        <div className="flex-1 ml-64 mr-80">
            {console.log('[YOLO] Rendering component with state:', {
                loading,
                schedulesCount: schedules.length,
                coachingSessionsCount: coachingSessions.length,
                filteredSchedulesCount: filteredSchedules.length,
                typeFilteredSchedulesCount: typeFilteredSchedules.length,
                dataStatus
            })}
            
            <div className="p-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-4xl font-unbounded-900">SCHEDULES</h1>
                    <h2 className="text-4xl font-unbounded-700 text-gray-500">DISCOVERY CALLS</h2>
                </div>

                {/* Date Range Filter */}
                <div className="mb-4 bg-[#111315] rounded-xl p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-unbounded-700">DATE RANGE FILTER</h2>
                        <button 
                            onClick={() => setIsDateRangeActive(!isDateRangeActive)}
                            className={`px-4 py-2 rounded-lg font-unbounded-500 ${
                                isDateRangeActive ? 'bg-[#85C240] text-white' : 'bg-gray-700 text-gray-400'
                            }`}
                        >
                            {isDateRangeActive ? 'RANGE ACTIVE' : 'RANGE INACTIVE'}
                        </button>
                    </div>
                    <div className="flex items-center space-x-4">
                        <div className="flex-1">
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">START DATE</label>
                            <input
                                type="date"
                                value={dateRange.startDate instanceof Date ? dateRange.startDate.toISOString().split('T')[0] : ''}
                                onChange={(e) => {
                                    // Create a proper date object from the input value
                                    const dateStr = e.target.value; // YYYY-MM-DD format
                                    if (dateStr) {
                                        const [year, month, day] = dateStr.split('-').map(num => parseInt(num, 10));
                                        const newDate = new Date(year, month - 1, day); // Month is 0-indexed
                                        
                                        setDateRange(prev => ({
                                            ...prev,
                                            startDate: newDate
                                        }));
                                        
                                        console.log('Set start date:', newDate.toISOString());
                                    }
                                }}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                disabled={!isDateRangeActive}
                            />
                        </div>
                        <div className="flex-1">
                            <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">END DATE</label>
                            <input
                                type="date"
                                value={dateRange.endDate instanceof Date ? dateRange.endDate.toISOString().split('T')[0] : ''}
                                onChange={(e) => {
                                    // Create a proper date object from the input value
                                    const dateStr = e.target.value; // YYYY-MM-DD format
                                    if (dateStr) {
                                        const [year, month, day] = dateStr.split('-').map(num => parseInt(num, 10));
                                        const newDate = new Date(year, month - 1, day); // Month is 0-indexed
                                        
                                        setDateRange(prev => ({
                                            ...prev,
                                            endDate: newDate
                                        }));
                                        
                                        console.log('Set end date:', newDate.toISOString());
                                    }
                                }}
                                className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                disabled={!isDateRangeActive}
                            />
                        </div>
                    </div>
                </div>

                {/* Existing Date Navigation (only show when range is inactive) */}
                {!isDateRangeActive && (
                    <div className="mb-8 bg-[#111315] rounded-xl p-6">
                        <div className="flex items-center justify-between mb-6">
                            <h2 className="text-2xl font-unbounded-700">TODAY'S TASK</h2>
                            <div className="flex items-center space-x-4">
                                <button 
                                    className="text-gray-500 hover:text-white"
                                    onClick={() => {
                                        const newDate = new Date(selectedDate);
                                        newDate.setDate(selectedDate.getDate() - 1);
                                        setSelectedDate(newDate);
                                        console.log('Selected date changed to:', newDate.toISOString());
                                    }}
                                >
                                    <span className="text-xl">←</span>
                                </button>
                                <span className="text-xl font-unbounded-700">
                                    {selectedDate.toLocaleDateString('en-US', { 
                                        month: 'long',
                                        day: 'numeric',
                                        year: 'numeric'
                                    }).toUpperCase()}
                                </span>
                                <button 
                                    className="text-gray-500 hover:text-white"
                                    onClick={() => {
                                        const newDate = new Date(selectedDate);
                                        newDate.setDate(selectedDate.getDate() + 1);
                                        setSelectedDate(newDate);
                                        console.log('Selected date changed to:', newDate.toISOString());
                                    }}
                                >
                                    <span className="text-xl">→</span>
                                </button>
                            </div>
                        </div>

                        {/* Calendar Strip */}
                        <div className="flex justify-between mb-6">
                            {generateCalendarDates().map((date, index) => (
                                <div key={index} className="flex flex-col items-center">
                                    <div className="text-gray-500 text-sm mb-2">
                                        {date.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase()}
                                    </div>
                                    <button
                                        onClick={() => {
                                            setSelectedDate(date);
                                            console.log('Calendar date selected:', date.toISOString());
                                        }}
                                        className={`w-12 h-12 rounded-lg flex items-center justify-center text-xl font-unbounded-700 ${
                                            date.toDateString() === selectedDate.toDateString()
                                                ? 'bg-[#85C240] text-white'
                                                : 'text-gray-500 hover:bg-gray-800'
                                        }`}
                                    >
                                        {date.getDate()}
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Filter Buttons */}
                <div className="mb-8 flex space-x-4">
                    <button
                        onClick={() => setSelectedFilter('ALL TASKS')}
                        className={`px-4 py-2 rounded-lg font-unbounded-500 ${
                            selectedFilter === 'ALL TASKS' ? 'bg-[#111315] text-white' : 'bg-gray-800 text-gray-400'
                        }`}
                    >
                        ALL TASKS
                    </button>
                    <button
                        onClick={() => setSelectedFilter('DISCOVERY CALL')}
                        className={`px-4 py-2 rounded-lg font-unbounded-500 ${
                            selectedFilter === 'DISCOVERY CALL' ? 'bg-[#85C240] text-white' : 'bg-gray-800 text-gray-400'
                        }`}
                    >
                        DISCOVERY CALLS
                    </button>
                    <button
                        onClick={() => setSelectedFilter('COACHING SESSION')}
                        className={`px-4 py-2 rounded-lg font-unbounded-500 ${
                            selectedFilter === 'COACHING SESSION' ? 'bg-[#85C240] text-white' : 'bg-gray-800 text-gray-400'
                        }`}
                    >
                        COACHING SESSIONS
                        {coachingSessions.length > 0 && (
                            <span className="ml-2 inline-flex items-center justify-center w-6 h-6 bg-white bg-opacity-20 rounded-full text-xs">
                                {coachingSessions.length}
                            </span>
                        )}
                    </button>
                    <button
                        onClick={() => setSelectedFilter('CHECK IN')}
                        className={`px-4 py-2 rounded-lg font-unbounded-500 ${
                            selectedFilter === 'CHECK IN' ? 'bg-[#FF7A50] text-white' : 'bg-gray-800 text-gray-400'
                        }`}
                    >
                        CHECK INS
                    </button>
                </div>

                {/* Schedule List */}
                <div className="space-y-4">
                    {isEditing ? (
                        <EditTask
                            schedule={editingSchedule}
                            onClose={handleEditClose}
                            onSave={handleEditSave}
                        />
                    ) : loading ? (
                        <div className="flex justify-center py-8">
                            <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-[#85C240]"></div>
                        </div>
                    ) : (
                        typeFilteredSchedules.length === 0 ? (
                            <div className="text-center text-gray-500 py-8">
                                <p className="font-unbounded-500">No schedules for this date</p>
                            </div>
                        ) : (
                            typeFilteredSchedules.map((schedule) => (
                                <div 
                                    key={schedule.id} 
                                    className={`flex items-center ${schedule.type === 'coaching session' ? 'bg-[#85C240] bg-opacity-10' : 'bg-[#111315]'} p-4 rounded-xl`}
                                >
                                    <input
                                        type="checkbox"
                                        className="mr-4 h-5 w-5 rounded border-gray-600 bg-transparent"
                                    />
                                    <div className="flex-1">
                                        <h4 className="font-unbounded-700">{schedule.name}</h4>
                                        <p className="text-sm text-gray-400 mt-1">{schedule.topic}</p>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <span className="text-sm text-gray-500">
                                                {schedule.time}
                                            </span>
                                            <span className="text-sm text-gray-500">•</span>
                                            <span className="text-sm text-gray-500">
                                                {schedule.date}
                                            </span>
                                            {schedule.dayOfWeek && (
                                                <>
                                                    <span className="text-sm text-gray-500">•</span>
                                                    <span className="text-sm text-gray-500">
                                                        {schedule.dayOfWeek}
                                                    </span>
                                                </>
                                            )}
                                            {schedule.planCode && (
                                                <>
                                                    <span className="text-sm text-gray-500">•</span>
                                                    <span className="text-sm text-[#85C240]">
                                                        Code: {schedule.planCode}
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                        {schedule.meetingLink && (
                                            <div className="mt-1">
                                                <a 
                                                    href={schedule.meetingLink} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    className="text-sm text-blue-400 hover:underline"
                                                >
                                                    {schedule.meetingLink}
                                                </a>
                                            </div>
                                        )}
                                    </div>
                                    <div className={`px-4 py-2 rounded-lg text-sm font-unbounded-500 ${getTaskTypeStyle(schedule.type)}`}>
                                        {schedule.type.toUpperCase()}
                                    </div>
                                    <button 
                                        className="ml-4 text-[#85C240] hover:text-green-400"
                                        onClick={() => handleGenerateCode(schedule)}
                                    >
                                        <FontAwesomeIcon icon={faUnlock} />
                                    </button>
                                    <button 
                                        className="ml-4 text-gray-400 hover:text-gray-300"
                                        onClick={() => handleEdit(schedule)}
                                    >
                                        <FontAwesomeIcon icon={faEdit} className="text-light-gray" />
                                    </button>
                                    <button 
                                        className="ml-4 text-red-400 hover:text-red-300" 
                                        onClick={() => handleDelete(schedule.id)}
                                    >
                                        <FontAwesomeIcon icon={faTrash} />
                                    </button>
                                </div>
                            ))
                        )
                    )}
                </div>
            </div>

            {showCoachingPlanModal && (
                <CoachingPlanModal 
                    onClose={() => setShowCoachingPlanModal(false)} 
                    schedule={selectedSchedule}
                    onSessionsCreated={handleSessionsCreated}
                />
            )}
        </div>
    );
} 
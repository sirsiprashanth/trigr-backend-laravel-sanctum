import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig';
import { collection, query, where, getDocs, addDoc, doc, updateDoc } from 'firebase/firestore';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTimes, faSpinner } from '@fortawesome/free-solid-svg-icons';

export default function CoachingPlanModal({ onClose, schedule, onSessionsCreated }) {
    const [planTitle, setPlanTitle] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [firstSessionDate, setFirstSessionDate] = useState('');
    const [firstSessionTime, setFirstSessionTime] = useState('');
    const [meetingLink, setMeetingLink] = useState('');
    const [generatedCode, setGeneratedCode] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState('');

    // Generate time slots in 30-minute intervals
    const generateTimeSlots = () => {
        const slots = [];
        for (let hour = 0; hour < 24; hour++) {
            for (let minute of ['00', '30']) {
                const time = `${hour.toString().padStart(2, '0')}:${minute}`;
                const formattedTime = new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                slots.push({ value: time, label: formattedTime });
            }
        }
        return slots;
    };

    const timeSlots = generateTimeSlots();

    useEffect(() => {
        const checkExistingPlan = async () => {
            try {
                const plansRef = collection(db, 'coachingPlans');
                const q = query(
                    plansRef, 
                    where('userId', '==', schedule.userId),
                    where('coachId', '==', schedule.coachId)
                );
                const snapshot = await getDocs(q);
                
                if (!snapshot.empty) {
                    const existingPlan = snapshot.docs[0].data();
                    setGeneratedCode(existingPlan.planCode);
                }
                setIsLoading(false);
            } catch (error) {
                console.error('Error checking existing plan:', error);
                setError('Error checking existing plan');
                setIsLoading(false);
            }
        };

        checkExistingPlan();
    }, [schedule]);

    // Generate a random 6-digit code
    const generateCode = () => {
        return Math.floor(100000 + Math.random() * 900000).toString();
    };

    // Check if code exists
    const checkCodeExists = async (code) => {
        const plansRef = collection(db, 'coachingPlans');
        const q = query(plansRef, where('planCode', '==', code));
        const snapshot = await getDocs(q);
        return !snapshot.empty;
    };

    // Generate a unique code
    const generateUniqueCode = async () => {
        let code = generateCode();
        let exists = await checkCodeExists(code);
        
        while (exists) {
            code = generateCode();
            exists = await checkCodeExists(code);
        }
        
        return code;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsLoading(true);
        setError('');

        try {
            if (!planTitle || !startDate || !endDate || !firstSessionDate || !firstSessionTime || !meetingLink) {
                throw new Error('Please fill in all fields');
            }

            if (!schedule.userId || !schedule.coachId) {
                console.error('[YOLO] Missing required data:', { schedule });
                throw new Error('Missing required user or coach data');
            }

            const code = await generateUniqueCode();
            
            const planData = {
                planCode: code,
                title: planTitle,
                startDate,
                endDate,
                firstSessionDate,
                firstSessionTime,
                meetingLink,
                userId: schedule.userId,
                coachId: schedule.coachId,
                createdAt: new Date(),
                status: 'pending',
                goals: [],
                focusAreas: [],
                strategies: [],
                actionPlans: []
            };

            console.log('[YOLO] Creating coaching plan with data:', planData);
            
            // Create coaching plan
            const planRef = await addDoc(collection(db, 'coachingPlans'), planData);
            console.log('[YOLO] Coaching plan created with ID:', planRef.id);

            // Create weekly recurring sessions
            try {
                const sessions = await createRecurringSessions(planData);
                console.log('[YOLO] Successfully created', sessions.length, 'recurring sessions');
                
                // Notify parent component about sessions being created
                if (typeof onSessionsCreated === 'function') {
                    onSessionsCreated(sessions);
                }
            } catch (sessionError) {
                console.error('[YOLO] Error creating sessions:', sessionError);
                setError('Code created but failed to create sessions: ' + sessionError.message);
            }

            // Update discovery call request status
            const requestRef = doc(db, 'discoveryCallRequest', schedule.id);
            await updateDoc(requestRef, {
                status: 'code created & shared'
            });
            console.log('[YOLO] Updated discovery call request status to "code created & shared"');

            setGeneratedCode(code);
            setIsLoading(false);
        } catch (error) {
            console.error('[YOLO] Error creating coaching plan:', error);
            setError(error.message);
            setIsLoading(false);
        }
    };

    // Function to create weekly recurring sessions
    const createRecurringSessions = async (planData) => {
        try {
            console.log('[YOLO] Creating recurring sessions with plan data:', planData);
            
            // Parse the first session date
            const firstDate = new Date(planData.firstSessionDate + 'T' + planData.firstSessionTime);
            const dayOfWeek = firstDate.getDay(); // 0 = Sunday, 1 = Monday, etc.
            const dayName = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(firstDate);
            
            console.log('[YOLO] First session day of week:', { 
                dayOfWeek, 
                dayName,
                firstDate: firstDate.toISOString() 
            });
            
            // Calculate the number of weeks between start and end date
            const startDate = new Date(planData.startDate);
            const endDate = new Date(planData.endDate);
            const diffTime = endDate - startDate;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const numberOfWeeks = Math.ceil(diffDays / 7);
            
            console.log('[YOLO] Coaching plan duration:', { 
                diffDays, 
                numberOfWeeks,
                startDate: startDate.toISOString(),
                endDate: endDate.toISOString()
            });
            
            // Find the first occurrence of the day of week after or on the start date
            let firstOccurrence = new Date(startDate);
            
            // If the first session date is before the start date, use the start date as base
            // Otherwise, use the first session date
            if (firstDate < startDate) {
                // Adjust to the next occurrence of the day of week from start date
                const currentDayOfWeek = firstOccurrence.getDay();
                const daysToAdd = (dayOfWeek - currentDayOfWeek + 7) % 7;
                firstOccurrence.setDate(firstOccurrence.getDate() + daysToAdd);
            } else {
                // Use the first session date as is
                firstOccurrence = new Date(firstDate);
            }
            
            console.log('[YOLO] First occurrence of session:', firstOccurrence.toISOString());
            
            // Create sessions for each week from the first occurrence
            const sessions = [];
            let currentDate = new Date(firstOccurrence);
            
            while (currentDate <= endDate) {
                // Format dates for Firestore
                const formattedDate = currentDate.toISOString().split('T')[0];
                const formattedTime = planData.firstSessionTime;
                
                const sessionNumber = sessions.length + 1;
                
                const sessionData = {
                    planCode: planData.planCode,
                    sessionNumber: sessionNumber,
                    sessionLabel: sessionNumber === 1 ? 'First Session' : `Session ${sessionNumber}`,
                    date: formattedDate,
                    time: formattedTime,
                    meetingLink: planData.meetingLink,
                    userId: planData.userId,
                    coachId: planData.coachId,
                    planTitle: planData.title,
                    status: 'scheduled',
                    createdAt: new Date(),
                    dayOfWeek: dayName // Store the day name for reference
                };
                
                console.log(`[YOLO] Creating session ${sessionNumber}:`, sessionData);
                
                // Create the session in Firestore
                await addDoc(collection(db, 'coachingSessions'), sessionData);
                sessions.push(sessionData);
                
                // Move to next week, same day of week
                currentDate.setDate(currentDate.getDate() + 7);
            }
            
            console.log(`[YOLO] Created ${sessions.length} weekly sessions`);
            return sessions;
        } catch (error) {
            console.error('[YOLO] Error creating recurring sessions:', error);
            throw new Error('Failed to create weekly sessions');
        }
    };

    if (isLoading) {
        return (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div className="bg-[#111315] rounded-xl p-6 w-full max-w-md flex items-center justify-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#85C240]"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-[#111315] rounded-xl p-6 w-full max-w-md">
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-2xl font-unbounded-700">
                        {generatedCode ? 'COACHING PLAN CODE' : 'CREATE COACHING PLAN'}
                    </h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-white">
                        <FontAwesomeIcon icon={faTimes} />
                    </button>
                </div>

                {error && (
                    <div className="bg-red-900 bg-opacity-30 border border-red-700 rounded-lg p-4 mb-4">
                        <p className="text-red-400">{error}</p>
                    </div>
                )}

                {generatedCode ? (
                    <>
                        <p className="mb-4 text-gray-400">
                            Here's the unique code for this coaching plan. Share it with your client to unlock access.
                        </p>
                        <div className="bg-[#222527] rounded-lg p-6 text-center">
                            <p className="text-4xl font-unbounded-700 text-[#85C240] tracking-widest">
                                {generatedCode}
                            </p>
                        </div>
                        <button
                            onClick={onClose}
                            className="w-full mt-6 bg-[#85C240] text-white py-3 rounded-lg font-unbounded-600 hover:bg-opacity-90 transition-all duration-300"
                        >
                            CLOSE
                        </button>
                    </>
                ) : (
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-4 mb-6">
                            <div>
                                <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">
                                    Coaching Plan Title*
                                </label>
                                <input
                                    type="text"
                                    value={planTitle}
                                    onChange={(e) => setPlanTitle(e.target.value)}
                                    className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                    placeholder="Enter Title"
                                    required
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">
                                        Start Date*
                                    </label>
                                    <input
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                        className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">
                                        End Date*
                                    </label>
                                    <input
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                        className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="border-t border-gray-700 pt-4">
                                <h3 className="text-lg font-unbounded-600 mb-4">Weekly Session Details</h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">
                                            First Session Date*
                                        </label>
                                        <input
                                            type="date"
                                            value={firstSessionDate}
                                            onChange={(e) => setFirstSessionDate(e.target.value)}
                                            className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                            required
                                        />
                                        <p className="text-xs text-gray-500 mt-1">
                                            Sessions will occur weekly on the same day
                                        </p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">
                                            Session Time*
                                        </label>
                                        <select
                                            value={firstSessionTime}
                                            onChange={(e) => setFirstSessionTime(e.target.value)}
                                            className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                            required
                                        >
                                            <option value="">Select Time</option>
                                            {timeSlots.map(slot => (
                                                <option key={slot.value} value={slot.value}>
                                                    {slot.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label className="block text-sm font-unbounded-500 text-gray-400 mb-2">
                                    Meeting Link*
                                </label>
                                <input
                                    type="text"
                                    value={meetingLink}
                                    onChange={(e) => setMeetingLink(e.target.value)}
                                    className="w-full bg-[#222527] text-white rounded-lg px-4 py-2 font-unbounded-500"
                                    placeholder="Enter meeting link"
                                    required
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    This link will be used for all weekly sessions
                                </p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            className="w-full bg-[#85C240] text-white py-3 rounded-lg font-unbounded-600 hover:bg-opacity-90 transition-all duration-300"
                            disabled={isLoading}
                        >
                            {isLoading ? (
                                <span className="flex items-center justify-center">
                                    <FontAwesomeIcon icon={faSpinner} spin className="mr-2" />
                                    GENERATING...
                                </span>
                            ) : 'GENERATE CODE'}
                        </button>
                    </form>
                )}
            </div>
        </div>
    );
} 
import { useState, useEffect } from 'react';
import { db } from '../../firebaseConfig';
import { collection, query, where, onSnapshot } from 'firebase/firestore';
import CoachingPlanDetails from './CoachingPlanDetails';

export default function CoachingPlansList({ currentUserId }) {
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedPlan, setSelectedPlan] = useState(null);

    useEffect(() => {
        if (!currentUserId) return;

        const plansRef = collection(db, 'coachingPlans');
        const q = query(plansRef, where('coachId', '==', Number(currentUserId)));

        const unsubscribe = onSnapshot(q, (snapshot) => {
            const plansData = snapshot.docs.map(doc => ({
                id: doc.id,
                ...doc.data()
            }));
            setPlans(plansData);
            setLoading(false);
        });

        return () => unsubscribe();
    }, [currentUserId]);

    if (loading) {
        return (
            <div className="flex-1 ml-64 mr-80 flex items-center justify-center">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#85C240]"></div>
            </div>
        );
    }

    if (selectedPlan) {
        return (
            <CoachingPlanDetails 
                plan={selectedPlan} 
                onBack={() => setSelectedPlan(null)} 
            />
        );
    }

    return (
        <div className="flex-1 ml-64 mr-80">
            <div className="p-8">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-4xl font-unbounded-900">MY PLANS</h1>
                    <h2 className="text-4xl font-unbounded-700 text-gray-500">COACHING PLANS</h2>
                </div>

                {/* Plans Grid */}
                <div className="grid grid-cols-2 gap-6">
                    {plans.map((plan) => (
                        <div
                            key={plan.id}
                            onClick={() => setSelectedPlan(plan)}
                            className="bg-[#111315] p-6 rounded-xl cursor-pointer hover:bg-opacity-80 transition-all duration-300"
                        >
                            <h3 className="text-xl font-unbounded-700 mb-2">{plan.title}</h3>
                            <div className="space-y-2">
                                <p className="text-sm text-gray-400">
                                    START DATE: {new Date(plan.startDate).toLocaleDateString()}
                                </p>
                                <p className="text-sm text-gray-400">
                                    COACH: {plan.coachName || 'PRASHANTH'}
                                </p>
                                <div className="flex items-center justify-between mt-4">
                                    <span className="text-[#85C240] text-sm font-unbounded-600">
                                        {plan.status.toUpperCase()}
                                    </span>
                                    <span className="text-gray-400">›</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
} 
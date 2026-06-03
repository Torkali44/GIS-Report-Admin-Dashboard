@extends('layouts.admin')

@section('title', 'تعديل بيانات المنزل')

@section('content')
    <a href="{{ route('admin.houses.index') }}" class="text-sm text-emerald-400 hover:underline">← العودة للقائمة</a>

    <h1 class="mt-4 text-2xl font-bold text-white">تعديل بيانات المنزل</h1>
    <p class="mt-1 text-slate-400">تحديث المعلومات الأساسية لهذا العقار.</p>

    <form method="post" action="{{ route('admin.houses.update', $house) }}" class="mt-8 space-y-10">
        @csrf
        @method('PUT')
        
        <div class="space-y-6">
            <h2 class="text-xl font-semibold text-emerald-400 border-b border-slate-800 pb-2">بيانات العقار</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="title" class="mb-1 block text-sm font-medium text-slate-300">عنوان التقرير / العقار</label>
                    <input id="title" name="title" type="text" required value="{{ old('title', $house->title) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none" placeholder="مثال: فيلا سكنية - مدينة سلمان">
                </div>
                <div>
                    <label for="activity" class="mb-1 block text-sm font-medium text-slate-300">النشاط بحسب رخصة البناء</label>
                    <input id="activity" name="activity" type="text" value="{{ old('activity', $house->activity ?? 'سكني') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="property_type" class="mb-1 block text-sm font-medium text-slate-300">نوع العقار</label>
                    <input id="property_type" name="property_type" type="text" value="{{ old('property_type', $house->property_type ?? 'فيلا') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="building_status" class="mb-1 block text-sm font-medium text-slate-300">حالة المبنى</label>
                    <input id="building_status" name="building_status" type="text" value="{{ old('building_status', $house->building_status ?? 'مشطب (غير مؤثث)') }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="document_number" class="mb-1 block text-sm font-medium text-slate-300">رقم الوثيقة</label>
                    <input id="document_number" name="document_number" type="text" value="{{ old('document_number', $house->document_number) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="intro_number" class="mb-1 block text-sm font-medium text-slate-300">رقم المقدمة</label>
                    <input id="intro_number" name="intro_number" type="text" value="{{ old('intro_number', $house->intro_number) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="villa_number" class="mb-1 block text-sm font-medium text-slate-300">فيلا</label>
                    <input id="villa_number" name="villa_number" type="text" value="{{ old('villa_number', $house->villa_number) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="road" class="mb-1 block text-sm font-medium text-slate-300">الطريق</label>
                    <input id="road" name="road" type="text" value="{{ old('road', $house->road) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="compound" class="mb-1 block text-sm font-medium text-slate-300">المجمع</label>
                    <input id="compound" name="compound" type="text" value="{{ old('compound', $house->compound) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="area" class="mb-1 block text-sm font-medium text-slate-300">المنطقة</label>
                    <input id="area" name="area" type="text" value="{{ old('area', $house->area) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="buyer_name" class="mb-1 block text-sm font-medium text-slate-300">المشتري</label>
                    <input id="buyer_name" name="buyer_name" type="text" value="{{ old('buyer_name', $house->buyer_name) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="id_number" class="mb-1 block text-sm font-medium text-slate-300">رقم الهوية</label>
                    <input id="id_number" name="id_number" type="text" value="{{ old('id_number', $house->id_number) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="developer_name" class="mb-1 block text-sm font-medium text-slate-300">اسم المطور العقاري</label>
                    <input id="developer_name" name="developer_name" type="text" value="{{ old('developer_name', $house->developer_name) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="engineering_supervisor" class="mb-1 block text-sm font-medium text-slate-300">المشرف الهندسي</label>
                    <input id="engineering_supervisor" name="engineering_supervisor" type="text" value="{{ old('engineering_supervisor', $house->engineering_supervisor) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="main_contractor" class="mb-1 block text-sm font-medium text-slate-300">المقاول الرئيسي</label>
                    <input id="main_contractor" name="main_contractor" type="text" value="{{ old('main_contractor', $house->main_contractor) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <h2 class="text-xl font-semibold text-emerald-400 border-b border-slate-800 pb-2">مواصفات العقار</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="property_age" class="mb-1 block text-sm font-medium text-slate-300">عمر العقار التقريبي</label>
                    <input id="property_age" name="property_age" type="text" value="{{ old('property_age', $house->property_age) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none" placeholder="مثال: 12 شهر">
                </div>
                <div>
                    <label for="land_area" class="mb-1 block text-sm font-medium text-slate-300">مساحة الأرض التقريبية</label>
                    <input id="land_area" name="land_area" type="text" value="{{ old('land_area', $house->land_area) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="building_area" class="mb-1 block text-sm font-medium text-slate-300">مساحة البناء التقريبية</label>
                    <input id="building_area" name="building_area" type="text" value="{{ old('building_area', $house->building_area) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="floors_count" class="mb-1 block text-sm font-medium text-slate-300">عدد الطوابق</label>
                    <input id="floors_count" name="floors_count" type="text" value="{{ old('floors_count', $house->floors_count) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="rooms_count" class="mb-1 block text-sm font-medium text-slate-300">عدد الغرف</label>
                    <input id="rooms_count" name="rooms_count" type="text" value="{{ old('rooms_count', $house->rooms_count) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="bathrooms_count" class="mb-1 block text-sm font-medium text-slate-300">عدد دورات المياه</label>
                    <input id="bathrooms_count" name="bathrooms_count" type="text" value="{{ old('bathrooms_count', $house->bathrooms_count) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="halls_count" class="mb-1 block text-sm font-medium text-slate-300">عدد الصالات</label>
                    <input id="halls_count" name="halls_count" type="text" value="{{ old('halls_count', $house->halls_count) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="parking_count" class="mb-1 block text-sm font-medium text-slate-300">عدد مواقف السيارات</label>
                    <input id="parking_count" name="parking_count" type="text" value="{{ old('parking_count', $house->parking_count) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="kitchens_count" class="mb-1 block text-sm font-medium text-slate-300">عدد المطابخ</label>
                    <input id="kitchens_count" name="kitchens_count" type="text" value="{{ old('kitchens_count', $house->kitchens_count) }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="total_percentage" class="mb-1 block text-sm font-medium text-emerald-400">النسبة الإجمالية (0 - 100)</label>
                    <input id="total_percentage" name="total_percentage" type="number" min="0" max="100" value="{{ old('total_percentage', $house->total_percentage) }}" class="w-full rounded-lg border border-emerald-500/30 bg-slate-950 px-3 py-2 text-white focus:border-emerald-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="pt-6">
            <button type="submit" class="rounded-lg bg-emerald-500 px-12 py-3 font-bold text-slate-950 hover:bg-emerald-400 transition-colors shadow-lg shadow-emerald-500/20">
                حفظ التغييرات
            </button>
            <a href="{{ route('admin.houses.index') }}" class="mr-4 rounded-lg bg-slate-800 px-8 py-3 font-bold text-white hover:bg-slate-700 transition-colors">
                إلغاء
            </a>
        </div>
    </form>
@endsection
